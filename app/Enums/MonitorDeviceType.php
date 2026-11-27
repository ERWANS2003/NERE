<?php

namespace App\Enums;

enum MonitorDeviceType: string
{
    case Camera = 'camera';
    case Printer = 'printer';
    case Server = 'server';
    case Antenna = 'antenna';
    case Network = 'network';

    public function label(): string
    {
        return match ($this) {
            self::Camera => 'Caméra',
            self::Printer => 'Imprimante',
            self::Server => 'Serveur',
            self::Antenna => 'Antenne',
            self::Network => 'Switch / routeur',
        };
    }

    /**
     * Modèle d'équipement : capteurs créés avec lui. Les capteurs SNMP ne sont
     * ajoutés que si on le demande, l'équipement devant avoir SNMP activé.
     *
     * @return list<array{kind: string, name: string, port?: int, parameter?: string}>
     */
    public function template(bool $snmp): array
    {
        $sensors = match ($this) {
            self::Camera => [
                ['kind' => 'ping', 'name' => 'Ping'],
                ['kind' => 'tcp', 'name' => 'Flux vidéo (RTSP)', 'port' => 554],
                ['kind' => 'http', 'name' => 'Interface web', 'port' => 80, 'parameter' => '/'],
            ],
            self::Printer => [
                ['kind' => 'ping', 'name' => 'Ping'],
                ['kind' => 'tcp', 'name' => "Port d'impression", 'port' => 9100],
            ],
            self::Server => [['kind' => 'ping', 'name' => 'Ping']],
            self::Antenna, self::Network => [
                ['kind' => 'ping', 'name' => 'Ping'],
                ['kind' => 'http', 'name' => 'Interface web', 'port' => 80, 'parameter' => '/'],
            ],
        };

        if ($snmp) {
            $sensors = [...$sensors, ...match ($this) {
                self::Server => [
                    ['kind' => 'snmp_uptime', 'name' => 'Durée de fonctionnement'],
                    ['kind' => 'snmp_cpu', 'name' => 'Processeur'],
                    ['kind' => 'snmp_memory', 'name' => 'Mémoire'],
                    ['kind' => 'snmp_disk', 'name' => 'Disque'],
                ],
                self::Printer => [
                    ['kind' => 'snmp_printer_status', 'name' => 'État de l’imprimante'],
                    ['kind' => 'snmp_printer', 'name' => 'Consommables'],
                    ['kind' => 'snmp_pages', 'name' => 'Compteur de pages'],
                ],
                self::Camera => [['kind' => 'snmp_uptime', 'name' => 'Durée de fonctionnement']],
                self::Antenna, self::Network => [
                    ['kind' => 'snmp_uptime', 'name' => 'Durée de fonctionnement'],
                    ['kind' => 'snmp_traffic', 'name' => 'Trafic'],
                ],
            }];
        }

        return $sensors;
    }

    /** Tracé SVG (24×24, trait) de l'icône du type, pour les cartes. */
    public function iconPath(): string
    {
        return match ($this) {
            self::Camera => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
            self::Printer => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z',
            self::Server => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
            self::Antenna => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
            self::Network => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        };
    }

    /**
     * Champs de la fiche saisie à la main : communs à tous, puis propres au type.
     *
     * @return array<string, array{label: string, input?: string, options?: list<string>, placeholder?: string}>
     */
    public function sheetFields(): array
    {
        $common = [
            'brand' => ['label' => 'Marque', 'placeholder' => match ($this) {
                self::Camera => 'Hikvision', self::Printer => 'Kyocera', self::Server => 'Dell', self::Antenna => 'Ubiquiti', self::Network => 'Cisco',
            }],
            'model' => ['label' => 'Modèle'],
            'serial' => ['label' => 'N° de série'],
            'location' => ['label' => 'Emplacement exact', 'placeholder' => 'Bâtiment, bureau, poteau…'],
        ];

        $specific = match ($this) {
            self::Camera => [
                'camera_kind' => ['label' => 'Forme', 'input' => 'select', 'options' => ['Dôme', 'Bullet', 'PTZ (motorisée)', 'Fisheye', 'Autre']],
                'mounting' => ['label' => 'Pose', 'input' => 'select', 'options' => ['Intérieur', 'Extérieur']],
                'area' => ['label' => 'Zone filmée', 'placeholder' => 'Portail nord, entrée usine…'],
                'resolution' => ['label' => 'Résolution', 'placeholder' => '4 MP'],
                'recorder' => ['label' => 'Enregistreur (NVR) et canal', 'placeholder' => 'NVR salle serveur, canal 5'],
                'power' => ['label' => 'Alimentation', 'placeholder' => 'PoE, 12 V…'],
            ],
            self::Printer => [
                'print_mode' => ['label' => 'Impression', 'input' => 'select', 'options' => ['Couleur', 'Noir et blanc']],
                'functions' => ['label' => 'Fonctions', 'placeholder' => 'Impression, copie, scan'],
                'served' => ['label' => 'Utilisée par', 'placeholder' => 'Service RH, 1er étage'],
                'toner_ref' => ['label' => 'Références des toners', 'placeholder' => 'TK-8368K, C, M, Y'],
                'service_contract' => ['label' => 'Contrat de maintenance'],
            ],
            self::Server => [
                'role' => ['label' => 'Rôle', 'placeholder' => 'Contrôleur de domaine, fichiers, intranet…'],
                'os' => ['label' => 'Système d’exploitation', 'placeholder' => 'Windows Server 2019'],
                'hosting' => ['label' => 'Type', 'input' => 'select', 'options' => ['Physique', 'Machine virtuelle']],
                'hypervisor' => ['label' => 'Hôte (si virtuel)', 'placeholder' => 'Hyper-V, VMware…'],
                'hardware' => ['label' => 'Processeur, mémoire, disques'],
                'backup' => ['label' => 'Sauvegarde', 'placeholder' => 'Veeam chaque nuit'],
            ],
            self::Antenna => [
                'radio_mode' => ['label' => 'Mode', 'input' => 'select', 'options' => ['Point d’accès', 'Station', 'Pont point à point', 'Autre']],
                'linked_to' => ['label' => 'Reliée à', 'placeholder' => 'Antenne du site, pylône…'],
                'frequency' => ['label' => 'Fréquence et canal', 'placeholder' => '5 GHz, canal 36'],
                'ssid' => ['label' => 'Nom du réseau (SSID)'],
                'mast' => ['label' => 'Support et hauteur', 'placeholder' => 'Pylône, 18 m'],
            ],
            self::Network => [
                'device_role' => ['label' => 'Rôle', 'input' => 'select', 'options' => ['Switch', 'Routeur', 'Pare-feu', 'Point d’accès Wi-Fi', 'Autre']],
                'ports' => ['label' => 'Nombre de ports', 'placeholder' => '24'],
                'firmware' => ['label' => 'Version du firmware'],
                'uplink' => ['label' => 'Lien montant', 'placeholder' => 'Port 24 vers le cœur de réseau'],
                'rack' => ['label' => 'Baie ou armoire'],
            ],
        };

        return $common + $specific + [
            'purchased_at' => ['label' => 'Date d’achat', 'input' => 'date'],
            'warranty_until' => ['label' => 'Fin de garantie', 'input' => 'date'],
            'supplier' => ['label' => 'Fournisseur ou prestataire'],
            'notes' => ['label' => 'Remarques', 'input' => 'textarea'],
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
