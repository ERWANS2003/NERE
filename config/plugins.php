<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plugins Activés
    |--------------------------------------------------------------------------
    |
    | Liste des plugins activés dans le système.
    | Ajoutez simplement le nom du plugin pour l'activer.
    |
    */
    'enabled' => [
        'AssetManagement' => true,
        'SafetyManagement' => true,
        'MaintenanceScheduler' => true,
        'InventoryControl' => true,
        'ProductionTracking' => true,
        'VehicleFleet' => true,
        'EnvironmentalCompliance' => true,
        'ContractorManagement' => true,
        'TrainingCertification' => true,
        'QualityAssurance' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des Plugins
    |--------------------------------------------------------------------------
    |
    | Configuration spécifique pour chaque plugin.
    |
    */

    'AssetManagement' => [
        'features' => [
            'depreciation_tracking' => true,
            'maintenance_scheduling' => true,
            'barcode_scanning' => true,
            'asset_lifecycle' => true,
        ],
        'notifications' => [
            'maintenance_due' => true,
            'warranty_expiring' => true,
            'calibration_required' => true,
        ],
    ],

    'SafetyManagement' => [
        'features' => [
            'incident_reporting' => true,
            'risk_assessment' => true,
            'safety_inspections' => true,
            'ppe_tracking' => true,
        ],
        'auto_escalation' => true,
        'critical_notification_delay' => 5, // minutes
    ],

    'MaintenanceScheduler' => [
        'features' => [
            'preventive_maintenance' => true,
            'work_orders' => true,
            'spare_parts_management' => true,
        ],
        'scheduling' => [
            'auto_schedule' => true,
            'conflict_detection' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Marketplace (Future)
    |--------------------------------------------------------------------------
    |
    | URL du marketplace pour télécharger des plugins tiers
    |
    */
    'marketplace' => [
        'enabled' => false,
        'url' => 'https://plugins.nere-mining.bf',
        'auto_update' => false,
    ],
];
