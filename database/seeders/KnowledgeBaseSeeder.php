<?php

namespace Database\Seeders;

use App\Models\KnowledgeArticle;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->articles() as $article) {
            KnowledgeArticle::query()->firstOrCreate(
                ['slug' => $article['slug']],
                $article,
            );
        }
    }

    /**
     * @return list<array{title: string, slug: string, summary: string, content: string, category: string, keywords: string, is_published: bool}>
     */
    private function articles(): array
    {
        return [
            [
                'title' => 'Je n’arrive pas à me connecter à mon compte',
                'slug' => 'probleme-connexion-compte',
                'summary' => 'Vérifiez les points courants avant de demander une intervention sur votre compte.',
                'content' => "Vérifiez que le verrouillage des majuscules est désactivé et que le clavier utilise la bonne langue.\nSaisissez à nouveau votre identifiant professionnel sans espace avant ou après.\nSi votre organisation met à disposition une procédure officielle de réinitialisation, utilisez-la ; ne communiquez jamais votre mot de passe à un collègue ou par courriel.\nSi le compte reste bloqué, si vous suspectez une connexion inhabituelle ou si la procédure de réinitialisation n’est pas disponible, contactez le support informatique.",
                'category' => 'access',
                'keywords' => 'connexion, compte, mot de passe, identifiant, verrouillé, accès',
                'is_published' => true,
            ],
            [
                'title' => 'Je n’ai plus de connexion au réseau',
                'slug' => 'depanner-connexion-reseau',
                'summary' => 'Quelques vérifications simples pour rétablir une connexion réseau ou Wi-Fi.',
                'content' => "Vérifiez que le Wi-Fi est activé et que le mode avion est désactivé.\nSi vous utilisez un câble réseau, vérifiez qu’il est bien branché aux deux extrémités.\nDéconnectez-vous puis reconnectez-vous au réseau professionnel habituel.\nEn cas de problème persistant, redémarrez votre poste si vous pouvez le faire sans interrompre un travail en cours.\nN’utilisez pas un réseau public ou personnel pour accéder à des données de l’entreprise. Si plusieurs collègues sont touchés, signalez-le au support.",
                'category' => 'network',
                'keywords' => 'réseau, wifi, internet, connexion, câble, vpn',
                'is_published' => true,
            ],
            [
                'title' => 'Mon imprimante ne répond pas',
                'slug' => 'depanner-imprimante',
                'summary' => 'Contrôlez la connexion, l’imprimante sélectionnée et les messages affichés.',
                'content' => "Vérifiez que l’imprimante est allumée et qu’aucun message ne signale un manque de papier ou une autre anomalie.\nDans la fenêtre d’impression, vérifiez que l’imprimante choisie est bien celle que vous souhaitez utiliser.\nVérifiez que votre poste et l’imprimante sont connectés au réseau professionnel.\nFermez puis rouvrez le document et relancez une seule impression pour éviter les doublons.\nSi le problème continue, notez le nom de l’imprimante et le message affiché avant de contacter le support.",
                'category' => 'hardware',
                'keywords' => 'imprimante, impression, papier, périphérique, matériel',
                'is_published' => true,
            ],
            [
                'title' => 'Mon ordinateur est lent ou ne répond plus',
                'slug' => 'ordinateur-lent-bloque',
                'summary' => 'Essayez ces gestes sans supprimer de fichiers professionnels.',
                'content' => "Enregistrez votre travail si le poste répond encore, puis fermez les applications dont vous n’avez plus besoin.\nAttendez quelques minutes si une mise à jour ou un traitement important est en cours.\nSi le poste reste bloqué, redémarrez-le lorsque cela ne risque pas d’interrompre un travail ou un transfert de fichiers.\nNe supprimez pas de fichiers système et n’installez pas de logiciel de nettoyage non autorisé.\nSi le ralentissement revient, indiquez au support les applications concernées et le moment où le problème survient.",
                'category' => 'hardware',
                'keywords' => 'ordinateur, lent, bloqué, redémarrer, mémoire, performance',
                'is_published' => true,
            ],
            [
                'title' => 'Je ne reçois ou n’envoie plus de courriels',
                'slug' => 'depanner-messagerie',
                'summary' => 'Vérifiez la connexion, le destinataire et les dossiers de votre messagerie.',
                'content' => "Vérifiez votre connexion au réseau professionnel puis actualisez votre messagerie.\nVérifiez que le destinataire et l’objet sont correctement renseignés.\nConsultez les dossiers Courrier indésirable, Éléments envoyés et Boîte d’envoi.\nSi une pièce jointe est volumineuse, essayez d’envoyer le message sans celle-ci ou utilisez le partage de fichiers approuvé par l’entreprise.\nNe transmettez pas d’informations sensibles à une adresse personnelle. Si le problème persiste, transmettez au support le message d’erreur sans y inclure de mot de passe.",
                'category' => 'email',
                'keywords' => 'courriel, email, messagerie, envoyer, recevoir, pièce jointe',
                'is_published' => true,
            ],
            [
                'title' => 'Une application ne démarre pas',
                'slug' => 'depanner-application',
                'summary' => 'Vérifiez les prérequis de base sans installer de logiciel non autorisé.',
                'content' => "Fermez l’application puis relancez-la.\nVérifiez que votre poste est connecté au réseau professionnel si l’application en dépend.\nRedémarrez le poste lorsque cela est possible, puis réessayez.\nNotez le nom de l’application, l’heure et le texte exact du message d’erreur.\nN’installez pas de correctif ou de logiciel téléchargé depuis un site non approuvé. Si le problème continue, transmettez ces informations au support.",
                'category' => 'software',
                'keywords' => 'application, logiciel, erreur, démarrage, mise à jour',
                'is_published' => true,
            ],
        ];
    }
}
