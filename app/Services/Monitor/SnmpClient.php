<?php

namespace App\Services\Monitor;

use RuntimeException;

/**
 * Client SNMP v2c minimal (GET et parcours d'une branche), en PHP pur : le
 * serveur n'a pas besoin de l'extension PHP SNMP. Lecture seule.
 */
class SnmpClient
{
    /**
     * @param  list<string>  $oids
     * @return array<string, int|float|string|null> valeur par OID (null si absent)
     */
    public function get(string $host, string $community, array $oids): array
    {
        $response = $this->request($host, $community, 0xA0, $oids);

        return array_replace(array_fill_keys(array_map(fn ($oid) => ltrim($oid, '.'), $oids), null), $response);
    }

    /**
     * Toutes les valeurs d'une branche, ex. la table des disques.
     *
     * @return array<string, int|float|string|null>
     */
    public function walk(string $host, string $community, string $base, int $max = 500): array
    {
        $base = ltrim($base, '.');
        $results = [];
        $current = $base;
        for ($i = 0; $i < $max; $i++) {
            $response = $this->request($host, $community, 0xA1, [$current]);
            $oid = array_key_first($response);
            if ($oid === null || ! str_starts_with($oid.'.', $base.'.') || $response[$oid] === null) {
                break;
            }
            $results[$oid] = $response[$oid];
            $current = $oid;
        }

        return $results;
    }

    /** @return array<string, int|float|string|null> */
    protected function request(string $host, string $community, int $pduType, array $oids): array
    {
        $requestId = random_int(1, 0x7FFFFFFF);
        $varbinds = '';
        foreach ($oids as $oid) {
            $varbinds .= $this->tlv(0x30, $this->encodeOid($oid)."\x05\x00");
        }
        $pdu = $this->tlv($pduType, $this->integer($requestId).$this->integer(0).$this->integer(0).$this->tlv(0x30, $varbinds));
        $packet = $this->tlv(0x30, $this->integer(1).$this->tlv(0x04, $community).$pdu);

        $socket = @stream_socket_client("udp://{$host}:161", $errno, $error, 2);
        if ($socket === false) {
            throw new RuntimeException('SNMP : impossible de joindre l’équipement');
        }
        $timeout = (float) config('monitor.snmp_timeout', 2);
        stream_set_timeout($socket, (int) $timeout, (int) (fmod($timeout, 1) * 1_000_000));
        // UDP peut perdre un paquet, et un appareil occupé répondre en retard :
        // on renvoie la question une fois avant de conclure à une absence de réponse.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            fwrite($socket, $packet);
            $data = fread($socket, 65535);
            $timedOut = stream_get_meta_data($socket)['timed_out'];
            if ($data !== false && $data !== '' && ! $timedOut) {
                break;
            }
        }
        fclose($socket);
        if ($data === false || $data === '' || $timedOut) {
            throw new RuntimeException('SNMP : pas de réponse (communauté ou SNMP désactivé ?)');
        }

        return $this->parseResponse($data, $requestId);
    }

    /** @return array<string, int|float|string|null> */
    protected function parseResponse(string $data, int $requestId): array
    {
        $offset = 0;
        [, $message] = $this->read($data, $offset);
        $pos = 0;
        $this->read($message, $pos); // version
        $this->read($message, $pos); // communauté
        [$type, $pdu] = $this->read($message, $pos);
        if ($type !== 0xA2) {
            throw new RuntimeException('SNMP : réponse inattendue');
        }
        $p = 0;
        [, $id] = $this->read($pdu, $p);
        [, $errorStatus] = $this->read($pdu, $p);
        $this->read($pdu, $p);
        if ($this->toInt($id) !== $requestId) {
            throw new RuntimeException('SNMP : réponse d’une autre requête');
        }
        if ($this->toInt($errorStatus) !== 0) {
            throw new RuntimeException('SNMP : l’équipement a refusé la requête (erreur '.$this->toInt($errorStatus).')');
        }
        [, $list] = $this->read($pdu, $p);

        $values = [];
        $l = 0;
        while ($l < strlen($list)) {
            [, $bind] = $this->read($list, $l);
            $b = 0;
            [, $oid] = $this->read($bind, $b);
            [$valueType, $value] = $this->read($bind, $b);
            $values[$this->decodeOid($oid)] = $this->decodeValue($valueType, $value);
        }

        return $values;
    }

    private function decodeValue(int $type, string $value): int|float|string|null
    {
        return match ($type) {
            0x02, 0x41, 0x42, 0x43, 0x46 => $this->toInt($value, signed: $type === 0x02),
            0x04 => $value,
            0x06 => $this->decodeOid($value),
            0x40 => implode('.', array_map('ord', str_split($value))),
            default => null, // NULL, noSuchObject, noSuchInstance, endOfMibView
        };
    }

    /** @return array{0: int, 1: string} */
    private function read(string $data, int &$offset): array
    {
        if ($offset + 2 > strlen($data)) {
            throw new RuntimeException('SNMP : réponse tronquée');
        }
        $type = ord($data[$offset++]);
        $length = ord($data[$offset++]);
        if ($length & 0x80) {
            $bytes = $length & 0x7F;
            $length = 0;
            for ($i = 0; $i < $bytes; $i++) {
                $length = ($length << 8) | ord($data[$offset++]);
            }
        }
        $value = substr($data, $offset, $length);
        $offset += $length;

        return [$type, $value];
    }

    private function toInt(string $bytes, bool $signed = false): int|float
    {
        $value = 0;
        foreach (str_split($bytes) as $byte) {
            $value = $value * 256 + ord($byte);
        }
        if ($signed && $bytes !== '' && ord($bytes[0]) & 0x80) {
            $value -= 2 ** (8 * strlen($bytes));
        }

        return $value;
    }

    private function tlv(int $type, string $value): string
    {
        $length = strlen($value);
        if ($length < 0x80) {
            return chr($type).chr($length).$value;
        }
        $bytes = ltrim(pack('N', $length), "\0");

        return chr($type).chr(0x80 | strlen($bytes)).$bytes.$value;
    }

    private function integer(int $value): string
    {
        $bytes = ltrim(pack('N', $value), "\0");
        if ($bytes === '' || ord($bytes[0]) & 0x80) {
            $bytes = "\0".$bytes;
        }

        return $this->tlv(0x02, $bytes);
    }

    private function encodeOid(string $oid): string
    {
        $parts = array_map('intval', explode('.', ltrim($oid, '.')));
        $encoded = chr(40 * $parts[0] + ($parts[1] ?? 0));
        foreach (array_slice($parts, 2) as $part) {
            $chunk = chr($part & 0x7F);
            while ($part >>= 7) {
                $chunk = chr(0x80 | ($part & 0x7F)).$chunk;
            }
            $encoded .= $chunk;
        }

        return $this->tlv(0x06, $encoded);
    }

    private function decodeOid(string $bytes): string
    {
        $first = ord($bytes[0] ?? "\0");
        $parts = [intdiv($first, 40), $first % 40];
        $value = 0;
        for ($i = 1; $i < strlen($bytes); $i++) {
            $byte = ord($bytes[$i]);
            $value = ($value << 7) | ($byte & 0x7F);
            if (! ($byte & 0x80)) {
                $parts[] = $value;
                $value = 0;
            }
        }

        return implode('.', $parts);
    }
}
