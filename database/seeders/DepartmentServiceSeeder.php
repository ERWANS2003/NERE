<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentService;
use Illuminate\Database\Seeder;

/**
 * Services proposés par chaque département (onglet « Services » de la page
 * vitrine). Objectif : 5-6 services par département.
 *
 * Idempotent : chaque service est identifié par (département, nom). Support IT
 * est relié au module de tickets ; les autres destinations restent à configurer.
 */
class DepartmentServiceSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();

        $services = [
            'IT' => [
                ['name' => 'Support IT', 'description' => 'Signaler un incident informatique et suivre vos demandes d’assistance : matériel, logiciels, réseau, imprimantes et accès.', 'icon' => 'wrench'],
                ['name' => 'Demande de matériel', 'description' => 'Demander du nouveau matériel informatique (ordinateur, écran, clavier, etc.).', 'icon' => 'laptop'],
                ['name' => 'Création de compte', 'description' => 'Demander la création d\'un compte utilisateur ou l\'accès à une application.', 'icon' => 'user-plus'],
                ['name' => 'Téléphonie et réseau', 'description' => 'Demander une ligne téléphonique, un accès VPN ou signaler un problème réseau.', 'icon' => 'phone'],
                ['name' => 'Sauvegarde et restauration', 'description' => 'Demander la restauration de fichiers ou vérifier vos sauvegardes.', 'icon' => 'database'],
                ['name' => 'Sécurité informatique', 'description' => 'Signaler un e-mail suspect, un virus ou une tentative de phishing.', 'icon' => 'shield'],
            ],
            'RH' => [
                ['name' => 'Demande de congé', 'description' => 'Soumettre une demande de congé annuel, maladie ou autre.', 'icon' => 'calendar-days'],
                ['name' => 'Attestation de travail', 'description' => 'Demander une attestation de travail ou un certificat d\'emploi.', 'icon' => 'file-text'],
                ['name' => 'Fiche de paie', 'description' => 'Télécharger vos fiches de paie mensuelles.', 'icon' => 'receipt'],
                ['name' => 'Formations', 'description' => 'Consulter et s\'inscrire aux formations disponibles.', 'icon' => 'graduation-cap'],
                ['name' => 'Mise à jour du dossier', 'description' => 'Mettre à jour vos coordonnées, RIB ou situation familiale.', 'icon' => 'user-plus'],
                ['name' => 'Avance sur salaire', 'description' => 'Soumettre une demande d\'avance sur salaire.', 'icon' => 'wallet'],
            ],
            'HSE' => [
                ['name' => 'Déclaration d\'incident', 'description' => 'Signaler un incident de sécurité ou un accident de travail.', 'icon' => 'alert-triangle'],
                ['name' => 'Observation sécurité', 'description' => 'Soumettre une observation de sécurité ou une suggestion d\'amélioration.', 'icon' => 'eye'],
                ['name' => 'Matériel de protection (EPI)', 'description' => 'Demander ou remplacer du matériel de protection individuelle.', 'icon' => 'shield'],
                ['name' => 'Permis de travail', 'description' => 'Demander un permis de travail à chaud, en hauteur ou en espace confiné.', 'icon' => 'clipboard-check'],
                ['name' => 'Visite médicale', 'description' => 'Planifier votre visite médicale annuelle ou d\'aptitude.', 'icon' => 'heart-pulse'],
                ['name' => 'Fiche de données de sécurité', 'description' => 'Consulter les fiches de données de sécurité des produits chimiques.', 'icon' => 'file-text'],
            ],
            'SCM' => [
                ['name' => 'Demande d\'achat', 'description' => 'Soumettre une demande d\'achat de matériel ou fournitures.', 'icon' => 'shopping-cart'],
                ['name' => 'Gestion des stocks', 'description' => 'Consulter les niveaux de stock et demander du matériel.', 'icon' => 'package'],
                ['name' => 'Sortie de magasin', 'description' => 'Demander la sortie d\'un article du magasin central.', 'icon' => 'truck'],
                ['name' => 'Référencement fournisseur', 'description' => 'Proposer le référencement d\'un nouveau fournisseur.', 'icon' => 'handshake'],
                ['name' => 'Suivi de commande', 'description' => 'Suivre l\'état d\'avancement d\'une commande en cours.', 'icon' => 'clipboard-check'],
            ],
            'ADMIN' => [
                ['name' => 'Réservation de véhicule', 'description' => 'Réserver un véhicule de service pour un déplacement.', 'icon' => 'car'],
                ['name' => 'Réservation de salle', 'description' => 'Réserver une salle de réunion ou de formation.', 'icon' => 'door-open'],
                ['name' => 'Ordre de mission', 'description' => 'Établir un ordre de mission pour un déplacement professionnel.', 'icon' => 'map-pin'],
                ['name' => 'Courrier et colis', 'description' => 'Déposer ou suivre un courrier et des colis entrants/sortants.', 'icon' => 'mail'],
                ['name' => 'Fournitures de bureau', 'description' => 'Commander des fournitures de bureau et consommables.', 'icon' => 'pencil'],
            ],
            'MINING' => [
                ['name' => 'Rapports de production', 'description' => 'Accéder aux rapports quotidiens et mensuels de production.', 'icon' => 'chart-bar'],
                ['name' => 'Planning des équipes', 'description' => 'Consulter le planning des équipes de production (shifts).', 'icon' => 'calendar'],
                ['name' => 'Demande d\'intervention engin', 'description' => 'Signaler une panne ou demander la maintenance d\'un engin.', 'icon' => 'wrench'],
                ['name' => 'Plan de minage', 'description' => 'Consulter le plan de minage et les zones d\'extraction actives.', 'icon' => 'map'],
                ['name' => 'Rapport de forage', 'description' => 'Accéder aux rapports de forage et de sautage.', 'icon' => 'file-text'],
            ],
            'PROC' => [
                ['name' => 'Suivi de traitement', 'description' => 'Consulter les données de traitement en temps réel.', 'icon' => 'activity'],
                ['name' => 'Analyses de laboratoire', 'description' => 'Accéder aux résultats des analyses de laboratoire.', 'icon' => 'flask'],
                ['name' => 'Bilan métallurgique', 'description' => 'Consulter les bilans métallurgiques et taux de récupération.', 'icon' => 'chart-bar'],
                ['name' => 'Gestion des réactifs', 'description' => 'Suivre la consommation et commander des réactifs.', 'icon' => 'package'],
                ['name' => 'Maintenance usine', 'description' => 'Signaler un dysfonctionnement sur la ligne de traitement.', 'icon' => 'wrench'],
            ],
            'SEC' => [
                ['name' => 'Demande de badge', 'description' => 'Demander un badge d\'accès ou signaler sa perte.', 'icon' => 'id-card'],
                ['name' => 'Autorisation d\'accès', 'description' => 'Demander une autorisation d\'accès à une zone sécurisée.', 'icon' => 'key'],
                ['name' => 'Déclaration de vol', 'description' => 'Déclarer un vol, une perte ou un acte de malveillance.', 'icon' => 'alert-triangle'],
                ['name' => 'Escorte et convoi', 'description' => 'Demander une escorte sécurisée pour un transport de valeurs.', 'icon' => 'truck'],
                ['name' => 'Contrôle d\'accès visiteurs', 'description' => 'Annoncer la visite d\'un prestataire ou d\'un intervenant externe.', 'icon' => 'user-plus'],
            ],
            'RELCOM' => [
                ['name' => 'Projets communautaires', 'description' => 'Consulter les projets communautaires en cours.', 'icon' => 'users'],
                ['name' => 'Plaintes et suggestions', 'description' => 'Soumettre une plainte ou une suggestion des communautés.', 'icon' => 'message-square'],
                ['name' => 'Demande de don ou parrainage', 'description' => 'Soumettre une demande de don, de parrainage ou de mécénat.', 'icon' => 'handshake'],
                ['name' => 'Recrutement local', 'description' => 'Consulter les opportunités d\'emploi réservées aux communautés locales.', 'icon' => 'user-plus'],
                ['name' => 'Réunion de concertation', 'description' => 'Planifier une réunion de concertation avec les villages riverains.', 'icon' => 'calendar-days'],
            ],
        ];

        foreach ($services as $deptCode => $deptServices) {
            $dept = $departments->where('code', $deptCode)->first();
            if (! $dept) {
                continue;
            }

            foreach ($deptServices as $index => $service) {
                $supportIt = $deptCode === 'IT' && $service['name'] === 'Support IT';

                DepartmentService::updateOrCreate(
                    [
                        'department_id' => $dept->id,
                        'name' => $service['name'],
                    ],
                    [
                        'description' => $service['description'],
                        'icon' => $service['icon'],
                        'link_type' => $supportIt ? 'page' : 'form',
                        'link_url' => $supportIt ? '/intranet/tickets' : null,
                        'order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
