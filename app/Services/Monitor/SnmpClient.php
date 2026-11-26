<?php

namespace App\Services\Monitor;

use RuntimeException;

/**
 * Client SNMP minimal (GET et parcours d'une branche), en PHP pur : le serveur
 * n'a pas besoin de l'extension PHP SNMP. Lecture seule.
 *
 * - SNMP v2c : une communauté (chaîne de caractères) ;
 * - SNMPv3 (RFC 3414 et 3826) : un tableau {user, auth: SHA|SHA256,
 *   auth_password, priv: AES|none, priv_password}, messages signés et chiffrés.
 */
class SnmpClient
{
    /** Moteurs SNMPv3 découverts : identifiant, redémarrages et horloge par hôte. */
    private static array $engines = [];

    /** Clés localisées (calcul coûteux : 1 Mo haché par clé). */
    private static array $keys = [];

    private const REPORTS = [
        '1.3.6.1.6.3.15.1.1.1.0' => 'SNMPv3 : niveau de sécurité refusé par l’équipement',
        '1.3.6.1.6.3.15.1.1.2.0' => 'SNMPv3 : horloge de l’équipement non synchronisée',
        '1.3.6.1.6.3.15.1.1.3.0' => 'SNMPv3 : utilisateur inconnu de l’équipement',
        '1.3.6.1.6.3.15.1.1.4.0' => 'SNMPv3 : identifiant de moteur inconnu',
        '1.3.6.1.6.3.15.1.1.5.0' => 'SNMPv3 : mot de passe d’authentification refusé',
        '1.3.6.1.6.3.15.1.1.6.0' => 'SNMPv3 : mot de passe de chiffrement refusé',
    ];

    /**
     * @param  string|array<string, string>  $credentials  communauté v2c, ou identifiants SNMPv3
     * @param  list<string>  $oids
     * @return array<string, int|float|string|null> valeur par OID (null si absent)
     */
    public function get(string $host, string|array $credentials, array $oids): array
    {
        $response = $this->request($host, $credentials, 0xA0, $oids);

        return array_replace(array_fill_keys(array_map(fn ($oid) => ltrim($oid, '.'), $oids), null), $response);
    }

    /**
     * Toutes les valeurs d'une branche, ex. la table des disques.
     *
     * @return array<string, int|float|string|null>
     */
    public function walk(string $host, string|array $credentials, string $base, int $max = 500): array
    {
        $base = ltrim($base, '.');
        $results = [];
        $current = $base;
        for ($i = 0; $i < $max; $i++) {
            $response = $this->request($host, $credentials, 0xA1, [$current]);
            $oid = array_key_first($response);
            if ($oid === null || ! str_starts_with($oid.'.', $base.'.') || $response[$oid] === null) {
                break;
            }
            $results[$oid] = $response[$oid];
            $current = $oid;
        }

        return $results;
    }

    /** Clé localisée d'un mot de passe pour un moteur (RFC 3414, A.2). */
    public static function localizeKey(string $algo, string $password, string $engineId): string
    {
        return self::$keys["{$algo}|{$password}|{$engineId}"] ??= (function () use ($algo, $password, $engineId) {
            $ku = hash($algo, substr(str_repeat($password, intdiv(1048576, strlen($password)) + 1), 0, 1048576), true);

            return hash($algo, $ku.$engineId.$ku, true);
        })();
    }

    /** @return array<string, int|float|string|null> */
    protected function request(string $host, string|array $credentials, int $pduType, array $oids): array
    {
        $requestId = random_int(1, 0x7FFFFFFF);
        $varbinds = '';
        foreach ($oids as $oid) {
            $varbinds .= $this->tlv(0x30, $this->encodeOid($oid)."\x05\x00");
        }
        $pdu = $this->tlv($pduType, $this->integer($requestId).$this->integer(0).$this->integer(0).$this->tlv(0x30, $varbinds));

        if (is_array($credentials)) {
            return $this->requestV3($host, $credentials, $pdu, $requestId);
        }

        return $this->parseResponse($this->exchange($host, $this->tlv(0x30, $this->integer(1).$this->tlv(0x04, $credentials).$pdu)), $requestId);
    }

    /** @return array<string, int|float|string|null> réponse SNMP v2c */
    protected function parseResponse(string $data, int $requestId): array
    {
        $offset = 0;
        [, $message] = $this->read($data, $offset);
        $pos = 0;
        $this->read($message, $pos); // version
        $this->read($message, $pos); // communauté
        [$type, $body] = $this->read($message, $pos);

        return $this->parsePdu($type, $body, $requestId);
    }

    /** Envoie un message UDP et attend la réponse, avec un second envoi si besoin. */
    protected function exchange(string $host, string $packet): string
    {
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

        return $data;
    }

    /** @return array<string, int|float|string|null> */
    private function requestV3(string $host, array $v3, string $pdu, int $requestId): array
    {
        [$algo, $macLength] = ($v3['auth'] ?? 'SHA') === 'SHA256' ? ['sha256', 24] : ['sha1', 12];
        $priv = ($v3['priv'] ?? 'AES') === 'AES';

        for ($try = 0; $try < 2; $try++) {
            $engine = self::$engines[$host] ??= $this->discoverEngine($host);
            $boots = $engine['boots'];
            $time = $engine['time'] + (time() - $engine['at']);
            $authKey = self::localizeKey($algo, (string) $v3['auth_password'], $engine['id']);
            $privKey = $priv ? substr(self::localizeKey($algo, (string) $v3['priv_password'], $engine['id']), 0, 16) : null;

            $scoped = $this->tlv(0x30, $this->tlv(0x04, $engine['id']).$this->tlv(0x04, '').$pdu);
            $salt = random_bytes(8);
            $msgData = $priv
                ? $this->tlv(0x04, openssl_encrypt($scoped, 'aes-128-cfb', $privKey, OPENSSL_RAW_DATA, pack('NN', $boots, $time).$salt))
                : $scoped;
            // Le même identifiant de message pour la signature et l'envoi, sinon la signature ne correspond plus.
            $msgId = random_int(1, 0x7FFFFFFF);
            $message = fn (string $mac) => $this->v3Message($msgId, $engine['id'], $boots, $time, (string) $v3['user'], $mac, $priv ? $salt : '', $priv ? 0x07 : 0x05, $msgData);
            $mac = substr(hash_hmac($algo, $message(str_repeat("\0", $macLength)), $authKey, true), 0, $macLength);

            [$type, $body, $security] = $this->openV3($this->exchange($host, $message($mac)), $privKey);
            if ($type !== 0xA8) {
                return $this->parsePdu($type, $body, $requestId);
            }
            $reason = $this->firstOid($body);
            if ($reason !== '1.3.6.1.6.3.15.1.1.2.0' || $try > 0) {
                unset(self::$engines[$host]);
                throw new RuntimeException(self::REPORTS[$reason] ?? 'SNMPv3 : requête refusée par l’équipement');
            }
            // Horloge décalée : on reprend celle que l'équipement vient d'indiquer.
            self::$engines[$host] = ['id' => $security['engine'], 'boots' => $security['boots'], 'time' => $security['time'], 'at' => time()];
        }

        throw new RuntimeException('SNMPv3 : horloge de l’équipement non synchronisée');
    }

    /** Découverte du moteur : message sans sécurité, l'équipement répond par un rapport. */
    private function discoverEngine(string $host): array
    {
        $pdu = $this->tlv(0xA0, $this->integer(random_int(1, 0x7FFFFFFF)).$this->integer(0).$this->integer(0).$this->tlv(0x30, ''));
        $message = $this->v3Message(random_int(1, 0x7FFFFFFF), '', 0, 0, '', '', '', 0x04, $this->tlv(0x30, $this->tlv(0x04, '').$this->tlv(0x04, '').$pdu));
        [, , $security] = $this->openV3($this->exchange($host, $message), null);
        if ($security['engine'] === '') {
            throw new RuntimeException('SNMPv3 : l’équipement ne répond pas en SNMPv3');
        }

        return ['id' => $security['engine'], 'boots' => $security['boots'], 'time' => $security['time'], 'at' => time()];
    }

    private function v3Message(int $msgId, string $engineId, int $boots, int $time, string $user, string $mac, string $salt, int $flags, string $msgData): string
    {
        $global = $this->tlv(0x30, $this->integer($msgId).$this->integer(65507).$this->tlv(0x04, chr($flags)).$this->integer(3));
        $security = $this->tlv(0x30, $this->tlv(0x04, $engineId).$this->integer($boots).$this->integer($time).$this->tlv(0x04, $user).$this->tlv(0x04, $mac).$this->tlv(0x04, $salt));

        return $this->tlv(0x30, $this->integer(3).$global.$this->tlv(0x04, $security).$msgData);
    }

    /** @return array{0: int, 1: string, 2: array{engine: string, boots: int, time: int}} type et corps du PDU, paramètres de sécurité */
    private function openV3(string $data, ?string $privKey): array
    {
        $offset = 0;
        [, $message] = $this->read($data, $offset);
        $pos = 0;
        $this->read($message, $pos); // version
        $this->read($message, $pos); // données globales
        [, $securityBlob] = $this->read($message, $pos);
        $s = 0;
        [, $security] = $this->read($securityBlob, $s);
        $p = 0;
        [, $engine] = $this->read($security, $p);
        [, $boots] = $this->read($security, $p);
        [, $time] = $this->read($security, $p);
        $this->read($security, $p); // utilisateur
        $this->read($security, $p); // signature
        [, $salt] = $this->read($security, $p);
        $params = ['engine' => $engine, 'boots' => (int) $this->toInt($boots), 'time' => (int) $this->toInt($time)];

        [$dataType, $msgData] = $this->read($message, $pos);
        if ($dataType === 0x04) {
            if ($privKey === null) {
                throw new RuntimeException('SNMPv3 : réponse chiffrée inattendue');
            }
            $plain = openssl_decrypt($msgData, 'aes-128-cfb', $privKey, OPENSSL_RAW_DATA, pack('NN', $params['boots'], $params['time']).$salt);
            if ($plain === false) {
                throw new RuntimeException('SNMPv3 : réponse illisible (mot de passe de chiffrement ?)');
            }
            $c = 0;
            [, $msgData] = $this->read($plain, $c);
        }
        $q = 0;
        $this->read($msgData, $q); // contextEngineID
        $this->read($msgData, $q); // contextName
        [$type, $body] = $this->read($msgData, $q);

        return [$type, $body, $params];
    }

    private function firstOid(string $pdu): string
    {
        $p = 0;
        $this->read($pdu, $p);
        $this->read($pdu, $p);
        $this->read($pdu, $p);
        [, $list] = $this->read($pdu, $p);
        $l = 0;
        [, $bind] = $this->read($list, $l);
        $b = 0;
        [, $oid] = $this->read($bind, $b);

        return $this->decodeOid($oid);
    }

    /** @return array<string, int|float|string|null> */
    private function parsePdu(int $type, string $pdu, int $requestId): array
    {
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
    protected function read(string $data, int &$offset): array
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

    protected function toInt(string $bytes, bool $signed = false): int|float
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

    protected function tlv(int $type, string $value): string
    {
        $length = strlen($value);
        if ($length < 0x80) {
            return chr($type).chr($length).$value;
        }
        $bytes = ltrim(pack('N', $length), "\0");

        return chr($type).chr(0x80 | strlen($bytes)).$bytes.$value;
    }

    protected function integer(int $value): string
    {
        $bytes = ltrim(pack('N', $value), "\0");
        if ($bytes === '' || ord($bytes[0]) & 0x80) {
            $bytes = "\0".$bytes;
        }

        return $this->tlv(0x02, $bytes);
    }

    protected function encodeOid(string $oid): string
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

    protected function decodeOid(string $bytes): string
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
