<?php

namespace App\Support;

class DepartmentRequestCatalog
{
    /**
     * @return array<string, array<string, array{name: string, description: string, fields: list<array<string, mixed>>}>>
     */
    public static function forms(): array
    {
        return [
            'RH' => [
                'Attestation de travail' => [
                    'description' => 'Demandez une attestation de travail ou un certificat d’emploi.',
                    'fields' => [
                        self::textarea('purpose', 'Motif de la demande'),
                        self::select('format', 'Format souhaité', ['PDF', 'Papier']),
                    ],
                ],
                'Fiche de paie' => [
                    'description' => 'Demandez une copie de votre fiche de paie.',
                    'fields' => [
                        self::text('pay_period', 'Mois et année concernés', 'text'),
                        self::select('format', 'Format souhaité', ['PDF', 'Papier']),
                    ],
                ],
                'Formations' => [
                    'description' => 'Demandez une formation ou une inscription à une session.',
                    'fields' => [
                        self::text('training', 'Formation souhaitée'),
                        self::textarea('objectives', 'Objectifs et justification'),
                        self::text('preferred_date', 'Période souhaitée', 'text', false),
                    ],
                ],
                'Mise à jour du dossier' => [
                    'description' => 'Demandez la mise à jour de vos informations personnelles auprès des RH.',
                    'fields' => [
                        self::select('information_type', 'Information à mettre à jour', ['Coordonnées', 'Adresse', 'Situation familiale', 'Coordonnées bancaires', 'Autre']),
                        self::textarea('details', 'Détails de la mise à jour'),
                    ],
                ],
                'Avance sur salaire' => [
                    'description' => 'Soumettez une demande d’avance sur salaire pour examen par les RH.',
                    'fields' => [
                        self::number('amount', 'Montant demandé'),
                        self::textarea('reason', 'Motif de la demande'),
                        self::text('repayment_period', 'Période de remboursement souhaitée', 'text'),
                    ],
                ],
            ],
            'ADMIN' => [
                'Réservation de véhicule' => [
                    'description' => 'Demandez un véhicule de service pour un déplacement professionnel.',
                    'fields' => [
                        self::text('travel_date', 'Date et heure de départ', 'datetime-local'),
                        self::text('return_date', 'Date et heure de retour', 'datetime-local'),
                        self::text('destination', 'Destination'),
                        self::textarea('purpose', 'Motif du déplacement'),
                    ],
                ],
                'Réservation de salle' => [
                    'description' => 'Demandez une salle pour une réunion ou une formation.',
                    'fields' => [
                        self::text('event_date', 'Date de la réservation', 'date'),
                        self::text('start_time', 'Heure de début', 'time'),
                        self::text('end_time', 'Heure de fin', 'time'),
                        self::number('attendees', 'Nombre de participants'),
                        self::textarea('purpose', 'Objet de la réunion'),
                    ],
                ],
                'Ordre de mission' => [
                    'description' => 'Soumettez les informations nécessaires à la préparation d’un ordre de mission.',
                    'fields' => [
                        self::text('destination', 'Destination'),
                        self::text('start_date', 'Date de départ', 'date'),
                        self::text('end_date', 'Date de retour', 'date'),
                        self::textarea('purpose', 'Objet et programme de la mission'),
                    ],
                ],
                'Courrier et colis' => [
                    'description' => 'Demandez l’envoi ou le suivi d’un courrier ou d’un colis professionnel.',
                    'fields' => [
                        self::select('request_type', 'Type de demande', ['Envoi', 'Réception', 'Suivi']),
                        self::text('recipient', 'Destinataire ou référence'),
                        self::textarea('details', 'Détails de la demande'),
                    ],
                ],
                'Fournitures de bureau' => [
                    'description' => 'Demandez des fournitures de bureau pour votre activité.',
                    'fields' => [
                        self::text('item', 'Fourniture demandée'),
                        self::number('quantity', 'Quantité'),
                        self::textarea('justification', 'Service et justification'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function serviceForms(): array
    {
        return [
            'RH' => [
                'Attestation de travail' => 'Attestation de travail',
                'Fiche de paie' => 'Fiche de paie',
                'Formations' => 'Formations',
                'Mise à jour du dossier' => 'Mise à jour du dossier',
                'Avance sur salaire' => 'Avance sur salaire',
            ],
            'ADMIN' => [
                'Réservation de véhicule' => 'Réservation de véhicule',
                'Réservation de salle' => 'Réservation de salle',
                'Ordre de mission' => 'Ordre de mission',
                'Courrier et colis' => 'Courrier et colis',
                'Fournitures de bureau' => 'Fournitures de bureau',
            ],
        ];
    }

    private static function text(string $key, string $label, string $type = 'text', bool $required = true): array
    {
        return compact('key', 'label', 'type', 'required');
    }

    private static function textarea(string $key, string $label, bool $required = true): array
    {
        return self::text($key, $label, 'textarea', $required);
    }

    /**
     * @param  list<string>  $options
     */
    private static function select(string $key, string $label, array $options, bool $required = true): array
    {
        return [
            ...self::text($key, $label, 'select', $required),
            'options' => array_map(fn (string $option) => ['value' => $option, 'label' => $option], $options),
        ];
    }

    private static function number(string $key, string $label, bool $required = true): array
    {
        return self::text($key, $label, 'number', $required);
    }
}
