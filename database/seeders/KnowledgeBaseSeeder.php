<?php

namespace Database\Seeders;

use App\Models\KnowledgeArticle;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->articles() as $article) {
            $knowledgeArticle = KnowledgeArticle::query()->firstOrNew(['slug' => $article['slug']]);

            if (! $knowledgeArticle->exists || $knowledgeArticle->author_id === null) {
                $knowledgeArticle->fill($article)->save();
            }
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
                'summary' => 'Contrôlez l’identifiant, le clavier et le réseau, puis transmettez au support le message d’erreur exact si le compte reste inaccessible.',
                'content' => "1. Vérifiez que votre poste est connecté au réseau professionnel et que la date et l’heure affichées sont correctes.\n2. Vérifiez la langue du clavier, le verrouillage des majuscules et l’absence d’espace avant ou après votre identifiant.\n3. Saisissez votre mot de passe une seule fois avec attention. Après plusieurs échecs, arrêtez les essais pour éviter le verrouillage du compte.\n4. Si une procédure officielle de réinitialisation est disponible, ouvrez-la depuis le portail habituel de l’entreprise. Ne communiquez jamais votre mot de passe ou un code de validation à quelqu’un.\n5. Si le compte indique « verrouillé », si la réinitialisation échoue ou si vous ne reconnaissez pas une demande de connexion, contactez le support IT.\nÀ joindre au ticket : votre identifiant professionnel, l’heure de l’incident, le nom du service concerné et le texte ou une capture du message d’erreur (sans mot de passe ni code).",
                'category' => 'access',
                'keywords' => 'connexion, compte, mot de passe, identifiant, verrouillé, accès, erreur, authentification',
                'is_published' => true,
            ],
            [
                'title' => 'Je n’ai plus de connexion au réseau',
                'slug' => 'depanner-connexion-reseau',
                'summary' => 'Déterminez si la panne touche un seul service, votre poste ou plusieurs collègues avant de contacter le support.',
                'content' => "1. Vérifiez si d’autres applications ou sites professionnels fonctionnent. Notez le nom des services qui sont inaccessibles.\n2. En Wi-Fi, vérifiez que le Wi-Fi est activé, que le mode avion est désactivé et que vous utilisez le réseau professionnel habituel.\n3. Avec un câble, vérifiez visuellement qu’il est correctement branché. Ne débranchez pas d’équipement réseau ou industriel.\n4. Si vous êtes en télétravail, vérifiez votre connexion Internet et relancez uniquement le VPN fourni par l’entreprise, si vous en utilisez un.\n5. Enregistrez votre travail. Redémarrez le poste seulement si cela n’interrompt pas une opération ou un transfert en cours.\nN’utilisez pas de réseau public ou personnel pour contourner une panne. Si plusieurs collègues ou un site entier sont touchés, prévenez rapidement le support IT.\nÀ joindre au ticket : votre site ou bureau, le type de connexion (filaire, Wi-Fi ou VPN), l’heure de début et les services concernés.",
                'category' => 'network',
                'keywords' => 'réseau, wifi, internet, connexion, câble, vpn, panne, intranet',
                'is_published' => true,
            ],
            [
                'title' => 'Mon imprimante ne répond pas',
                'slug' => 'depanner-imprimante',
                'summary' => 'Vérifiez la file d’impression, le nom de l’imprimante et son état sans manipuler de pièces internes.',
                'content' => "1. Dans la fenêtre d’impression, confirmez que l’imprimante sélectionnée correspond au nom ou à l’étiquette de celle que vous souhaitez utiliser.\n2. Vérifiez que l’imprimante est allumée et relevez tout message affiché (papier, toner, capot ou erreur).\n3. Vérifiez uniquement les points accessibles indiqués par l’écran ou le guide local : papier dans le bac prévu, capot correctement fermé. Ne forcez pas un mécanisme et ne démontez pas l’appareil.\n4. Consultez la file d’impression. Si un document est bloqué, annulez uniquement votre propre tâche si l’option est disponible, puis attendez quelques instants.\n5. Relancez une seule impression d’essai pour éviter les doublons. Si plusieurs personnes sont concernées, demandez-leur de ne pas multiplier les envois.\nÀ joindre au ticket : le nom de l’imprimante, son emplacement, le message affiché et le nombre de personnes touchées.",
                'category' => 'hardware',
                'keywords' => 'imprimante, impression, papier, périphérique, matériel, file impression, toner',
                'is_published' => true,
            ],
            [
                'title' => 'Mon ordinateur est lent ou ne répond plus',
                'slug' => 'ordinateur-lent-bloque',
                'summary' => 'Protégez votre travail, identifiez les applications concernées et évitez les nettoyages ou redémarrages forcés.',
                'content' => "1. Si le poste répond encore, enregistrez vos fichiers et notez les applications qui ralentissent.\n2. Attendez quelques minutes si une mise à jour, une synchronisation ou un traitement important est en cours.\n3. Fermez normalement les applications que vous n’utilisez pas. Ne terminez pas un processus système et ne supprimez pas de fichiers pour libérer de l’espace.\n4. Si le poste est complètement figé, attendez et demandez conseil au support avant un arrêt forcé, surtout si des données sont en cours d’enregistrement ou si le poste pilote un équipement.\n5. N’installez pas d’outil de nettoyage ou de diagnostic non approuvé.\nÀ joindre au ticket : le modèle ou numéro d’inventaire du poste, les applications concernées, l’heure d’apparition et la fréquence du problème.",
                'category' => 'hardware',
                'keywords' => 'ordinateur, lent, bloqué, redémarrer, mémoire, performance, poste, figé',
                'is_published' => true,
            ],
            [
                'title' => 'Je ne reçois ou n’envoie plus de courriels',
                'slug' => 'depanner-messagerie',
                'summary' => 'Contrôlez la connexion et la boîte d’envoi, puis notez si le problème touche l’envoi, la réception ou les deux.',
                'content' => "1. Vérifiez que les autres services professionnels fonctionnent, puis actualisez la messagerie ou reconnectez-vous par le portail habituel.\n2. Pour un envoi, vérifiez l’adresse du destinataire, la taille des pièces jointes et la présence d’un message dans la Boîte d’envoi.\n3. Pour un message attendu, vérifiez les dossiers Courrier indésirable, Archives et Éléments supprimés. Demandez à l’expéditeur de confirmer l’adresse et l’heure d’envoi.\n4. Si une pièce jointe est volumineuse, utilisez uniquement le partage de fichiers approuvé par l’entreprise. N’envoyez pas de document professionnel vers une adresse personnelle.\n5. Si un message d’erreur apparaît, conservez son texte exact. Ne transmettez ni mot de passe ni code de connexion.\nÀ joindre au ticket : indiquer envoi ou réception, l’heure, le destinataire ou l’expéditeur professionnel (si nécessaire) et le texte d’erreur.",
                'category' => 'email',
                'keywords' => 'courriel, email, messagerie, envoyer, recevoir, pièce jointe, boîte envoi',
                'is_published' => true,
            ],
            [
                'title' => 'Une application ne démarre pas',
                'slug' => 'depanner-application',
                'summary' => 'Vérifiez le message d’erreur, le réseau et l’accès demandé sans installer de logiciel ni modifier les réglages système.',
                'content' => "1. Notez le nom et la version de l’application, l’heure et le texte exact de l’erreur.\n2. Fermez puis relancez l’application normalement. Si elle est utilisée pour une opération en cours, ne la fermez pas avant d’avoir sécurisé le travail.\n3. Vérifiez que votre compte est connecté et que le réseau professionnel fonctionne, si l’application en dépend.\n4. Si l’application signale un accès refusé, demandez à votre responsable de confirmer le droit attendu et ouvrez un ticket d’accès. N’utilisez pas le compte d’un collègue.\n5. Ne téléchargez pas de correctif et ne modifiez pas les paramètres de sécurité. Contactez le support si l’application ne démarre toujours pas.\nÀ joindre au ticket : nom de l’application, poste utilisé, texte ou capture de l’erreur et dernière action effectuée avant le problème.",
                'category' => 'software',
                'keywords' => 'application, logiciel, erreur, démarrage, mise à jour, accès refusé',
                'is_published' => true,
            ],
            [
                'title' => 'Je dois réinitialiser mon mot de passe ou mon compte est verrouillé',
                'slug' => 'reinitialiser-mot-de-passe-compte-verrouille',
                'summary' => 'Utilisez uniquement le parcours officiel de réinitialisation et ne partagez jamais vos codes.',
                'content' => "1. Arrêtez les tentatives répétées si le compte est verrouillé ou si vous n’êtes pas certain du mot de passe.\n2. Ouvrez le portail officiel de réinitialisation depuis un favori ou l’intranet de l’entreprise. Vérifiez l’adresse avant de saisir votre identifiant.\n3. Suivez les instructions affichées. Ne communiquez jamais votre mot de passe, un code reçu par SMS ou une notification d’approbation à une autre personne, y compris au support.\n4. Après la réinitialisation, mettez à jour le mot de passe dans les outils professionnels concernés en suivant les consignes internes.\n5. Si le portail est indisponible, si vous ne reconnaissez pas une demande d’authentification ou si le compte reste verrouillé, contactez le support IT par un canal connu.\nÀ joindre au ticket : identifiant professionnel, heure de la tentative et message affiché. N’incluez aucun secret ni code de validation.",
                'category' => 'access',
                'keywords' => 'mot de passe, réinitialiser, compte verrouillé, accès, code, authentification',
                'is_published' => true,
            ],
            [
                'title' => 'Le VPN ne se connecte pas depuis l’extérieur',
                'slug' => 'depanner-vpn-teletravail',
                'summary' => 'Vérifiez la connexion Internet et le client VPN fourni par l’entreprise, sans contourner la sécurité.',
                'content' => "1. Vérifiez que votre connexion Internet fonctionne avec un service autorisé.\n2. Lancez le client VPN installé et fourni par l’entreprise. Vérifiez que vous utilisez le profil officiel, sans modifier l’adresse du serveur.\n3. Acceptez uniquement une demande d’authentification que vous venez de déclencher. Refusez toute notification inattendue et signalez-la au support.\n4. Si un message indique un certificat expiré, un compte bloqué ou une configuration manquante, ne contournez pas l’avertissement et ne téléchargez pas de profil depuis un site tiers.\n5. Réessayez une fois après avoir fermé puis relancé le client. Si l’échec continue, contactez le support.\nÀ joindre au ticket : système utilisé, heure, nom du profil VPN et texte de l’erreur. N’envoyez ni mot de passe ni code MFA.",
                'category' => 'network',
                'keywords' => 'vpn, télétravail, accès distant, connexion, authentification, certificat',
                'is_published' => true,
            ],
            [
                'title' => 'Je n’ai pas accès à un dossier ou fichier partagé',
                'slug' => 'acces-dossier-fichier-partage',
                'summary' => 'Vérifiez le chemin et les droits attendus, puis faites confirmer l’accès par le responsable du dossier.',
                'content' => "1. Vérifiez que vous êtes connecté au réseau professionnel ou au VPN officiel lorsque vous travaillez à distance.\n2. Ouvrez le dossier depuis son emplacement habituel ou le lien interne fourni. Vérifiez que le nom du dossier est correct.\n3. Si le message indique « accès refusé », demandez au responsable du dossier de confirmer que vous devez y accéder et de valider la demande selon la procédure interne.\n4. Ne demandez pas les identifiants d’un collègue et ne copiez pas les fichiers vers un espace personnel pour contourner les droits.\n5. Si le dossier est introuvable ou si plusieurs utilisateurs ont perdu l’accès, signalez-le au support IT.\nÀ joindre au ticket : chemin ou nom du dossier, message affiché, poste utilisé et validation du responsable si elle est requise. Ne joignez pas de fichier confidentiel sans autorisation.",
                'category' => 'access',
                'keywords' => 'dossier partagé, fichier, accès refusé, droits, partage, réseau',
                'is_published' => true,
            ],
            [
                'title' => 'Je dois récupérer un fichier supprimé ou une ancienne version',
                'slug' => 'recuperer-fichier-supprime-ancienne-version',
                'summary' => 'Limitez les modifications sur l’emplacement concerné et demandez rapidement une restauration.',
                'content' => "1. Vérifiez la Corbeille de l’application ou de l’emplacement partagé, si elle est disponible.\n2. Si le fichier est encore présent mais incorrect, vérifiez l’historique des versions proposé par l’outil professionnel.\n3. Évitez de créer ou déplacer des fichiers portant le même nom dans le dossier concerné avant l’intervention du support.\n4. N’utilisez pas de logiciel de récupération non approuvé et ne restaurez pas un fichier sur un poste ou un dossier non autorisé.\n5. Ouvrez un ticket dès que possible. Indiquez l’emplacement, le nom du fichier, la dernière date à laquelle il était disponible et l’action observée.\nLe support vérifiera les possibilités de restauration selon les sauvegardes et les règles de conservation de l’entreprise. N’ajoutez pas le contenu du fichier au ticket s’il est confidentiel.",
                'category' => 'backup',
                'keywords' => 'sauvegarde, restauration, fichier supprimé, ancienne version, corbeille, récupération',
                'is_published' => true,
            ],
            [
                'title' => 'Je reçois un courriel suspect ou une demande inhabituelle',
                'slug' => 'signaler-courriel-suspect-hameconnage',
                'summary' => 'Ne cliquez pas, ne répondez pas et signalez le message par le canal sécurité habituel.',
                'content' => "1. N’ouvrez pas les liens et pièces jointes. Ne répondez pas et ne fournissez aucun mot de passe, code ou donnée professionnelle.\n2. Si vous n’avez pas interagi avec le message, utilisez la fonction « Signaler comme hameçonnage » ou le canal sécurité communiqué par l’entreprise.\n3. Si vous avez cliqué, ouvert une pièce jointe, saisi un mot de passe ou approuvé une connexion, contactez immédiatement le support ou le référent sécurité par un canal connu. N’attendez pas de voir si un problème survient.\n4. Ne transférez pas le message suspect à des collègues. Conservez-le selon les consignes internes afin que l’équipe IT puisse l’analyser.\n5. Si un poste semble compromis, cessez de l’utiliser pour accéder aux données de l’entreprise et suivez les instructions du support. N’éteignez pas un équipement opérationnel sans consigne.\nÀ signaler : heure de réception, expéditeur affiché, action éventuelle réalisée et service concerné. Ne publiez pas le message sur un espace public.",
                'category' => 'security',
                'keywords' => 'sécurité, phishing, hameçonnage, courriel suspect, fraude, mot de passe',
                'is_published' => true,
            ],
            [
                'title' => 'Le son ou le microphone ne fonctionne pas en réunion',
                'slug' => 'depanner-son-micro-reunion',
                'summary' => 'Vérifiez le périphérique choisi et les autorisations de l’application de réunion.',
                'content' => "1. Vérifiez que le casque ou le microphone est correctement branché et qu’il n’est pas en mode muet (bouton physique ou commande de l’application).\n2. Dans les paramètres audio de la réunion, sélectionnez le bon haut-parleur et le bon microphone, puis utilisez le test proposé.\n3. Fermez les autres applications de réunion qui pourraient déjà utiliser le périphérique.\n4. Si l’application demande une autorisation d’accès au microphone, acceptez-la uniquement pour l’application professionnelle attendue.\n5. Rejoignez à nouveau la réunion si cela ne vous fait pas perdre votre place ou votre travail. Si le problème persiste, utilisez une solution de repli autorisée et ouvrez un ticket.\nÀ joindre au ticket : nom de l’application, modèle du casque si connu, poste utilisé et résultat du test audio.",
                'category' => 'hardware',
                'keywords' => 'microphone, micro, casque, son, audio, réunion, visioconférence',
                'is_published' => true,
            ],
            [
                'title' => 'Une page ou un outil web professionnel ne se charge pas',
                'slug' => 'depanner-page-web-professionnelle',
                'summary' => 'Vérifiez l’adresse, la connexion et le navigateur approuvé avant de signaler une panne.',
                'content' => "1. Vérifiez que l’adresse correspond au favori ou au lien interne habituel. Ne poursuivez pas si le navigateur signale un certificat ou une adresse suspecte.\n2. Actualisez la page une seule fois et vérifiez si d’autres services professionnels sont accessibles.\n3. Si cela est autorisé, essayez le navigateur approuvé par l’entreprise. Ne désactivez pas les protections et ne modifiez pas les paramètres proxy ou certificat.\n4. Notez le texte de l’erreur, l’heure et l’adresse du service (en masquant les paramètres contenant des données personnelles ou des jetons).\n5. Si d’autres collègues sont concernés, ou si le service est nécessaire à une opération en cours, prévenez le support IT rapidement.\nÀ joindre au ticket : navigateur utilisé, service concerné, message affiché et nombre de personnes touchées.",
                'category' => 'software',
                'keywords' => 'site web, navigateur, page inaccessible, intranet, certificat, application web',
                'is_published' => true,
            ],
            [
                'title' => 'Mon clavier, ma souris ou un périphérique USB ne fonctionne plus',
                'slug' => 'depanner-clavier-souris-usb',
                'summary' => 'Contrôlez le branchement et testez un autre port autorisé sans installer de pilote.',
                'content' => "1. Si le périphérique est sans fil, vérifiez son interrupteur et son niveau de batterie si cette information est disponible.\n2. Pour un périphérique USB autorisé, vérifiez le branchement et essayez un autre port disponible sur le poste, sans débrancher un équipement critique.\n3. Vérifiez si le problème concerne un seul périphérique ou plusieurs. Si un autre périphérique identique approuvé est disponible, testez-le.\n4. Ne branchez pas de clé USB inconnue et n’installez pas de pilote téléchargé sur Internet.\n5. Si le périphérique est endommagé, chauffe, présente une odeur inhabituelle ou est nécessaire à une opération, cessez de l’utiliser et prévenez le support.\nÀ joindre au ticket : type et modèle du périphérique, poste ou numéro d’inventaire et tests déjà effectués.",
                'category' => 'hardware',
                'keywords' => 'clavier, souris, usb, périphérique, matériel, port, batterie',
                'is_published' => true,
            ],
            [
                'title' => 'Je ne peux pas installer ou mettre à jour un logiciel professionnel',
                'slug' => 'demander-installation-mise-a-jour-logiciel',
                'summary' => 'Les installations doivent être validées et réalisées par le canal informatique approuvé.',
                'content' => "1. Vérifiez si le logiciel figure déjà dans le catalogue d’applications ou le portail logiciel de l’entreprise.\n2. Consultez les consignes de votre responsable pour confirmer que le logiciel est nécessaire à votre activité.\n3. Si l’installation est bloquée ou nécessite des droits administrateur, ne tentez pas de contourner cette restriction. Ouvrez une demande au support IT.\n4. N’utilisez pas de compte administrateur partagé, de clé d’activation personnelle ou de site de téléchargement non approuvé.\n5. Pour une mise à jour, enregistrez votre travail et suivez les consignes de l’application ou du support ; ne mettez pas à jour un outil utilisé pour une opération critique sans validation.\nÀ joindre à la demande : nom du logiciel, usage professionnel, version souhaitée si connue et message affiché.",
                'category' => 'software',
                'keywords' => 'installation, logiciel, mise à jour, droits administrateur, application, catalogue',
                'is_published' => true,
            ],
            [
                'title' => 'Un téléphone professionnel ne se connecte plus au Wi-Fi ou à la messagerie',
                'slug' => 'depanner-telephone-professionnel',
                'summary' => 'Vérifiez les connexions et les réglages autorisés sans supprimer le profil professionnel.',
                'content' => "1. Vérifiez que le mode avion est désactivé et que le Wi-Fi ou les données mobiles sont activés selon les consignes internes.\n2. Pour le Wi-Fi, sélectionnez le réseau professionnel enregistré. Ne tentez pas de contourner une authentification ou d’utiliser un réseau public pour accéder aux données de l’entreprise.\n3. Ouvrez l’application professionnelle et vérifiez qu’elle ne demande pas une nouvelle authentification officielle.\n4. Ne supprimez pas le profil de gestion, le compte professionnel ou les données de l’appareil. Ne réinitialisez pas le téléphone sans consigne du support.\n5. Si l’appareil est perdu, volé ou affiche une activité inconnue, prévenez immédiatement le support IT par un autre appareil ou un canal connu.\nÀ joindre au ticket : modèle de l’appareil, application ou connexion concernée, heure et message affiché. Ne communiquez aucun code de déverrouillage.",
                'category' => 'network',
                'keywords' => 'téléphone, mobile, wifi, messagerie, appareil professionnel, perte, vol',
                'is_published' => true,
            ],
            [
                'title' => 'Mon espace de stockage professionnel est presque plein',
                'slug' => 'espace-stockage-professionnel-presque-plein',
                'summary' => 'Repérez les fichiers volumineux sans supprimer des données métier ni déplacer des documents vers un espace personnel.',
                'content' => "1. Notez le message d’avertissement et l’emplacement concerné (poste, espace partagé ou service de fichiers professionnel).\n2. Vérifiez les fichiers volumineux dont vous êtes propriétaire et dont la conservation n’est plus nécessaire, en respectant les règles de l’entreprise.\n3. Videz la corbeille de cet espace uniquement après avoir vérifié son contenu et les règles de conservation applicables.\n4. Ne supprimez pas de fichiers système, de dossiers partagés ou de documents métier pour libérer de la place. Ne transférez pas de données professionnelles vers un compte personnel ou une clé USB non approuvée.\n5. Si l’espace reste saturé ou si des fichiers doivent être archivés, demandez conseil au responsable des données ou au support IT.\nÀ joindre au ticket : nom de l’espace, message affiché, capacité indiquée et besoin estimé.",
                'category' => 'software',
                'keywords' => 'stockage, espace disque, quota, plein, fichiers volumineux, archivage',
                'is_published' => true,
            ],
            [
                'title' => 'Mon ordinateur ne s’allume pas ou ne charge plus',
                'slug' => 'ordinateur-ne-sallume-pas-ne-charge-plus',
                'summary' => 'Vérifiez l’alimentation visible et les voyants sans ouvrir le poste ni manipuler une batterie endommagée.',
                'content' => "1. Vérifiez que la prise ou la multiprise est alimentée et que le câble d’alimentation est correctement branché aux deux extrémités.\n2. Si vous utilisez un chargeur professionnel habituel, vérifiez visuellement son état et le voyant de charge éventuel.\n3. Débranchez les périphériques non essentiels puis essayez une seule fois de rallumer normalement le poste.\n4. N’ouvrez pas l’ordinateur, ne retirez pas une batterie intégrée et n’utilisez pas un chargeur non approuvé. Si la batterie gonfle, chauffe, fuit ou dégage une odeur, cessez immédiatement de manipuler l’appareil et avertissez le support.\n5. Si le poste reste éteint, demandez un équipement de remplacement selon la procédure interne.\nÀ joindre au ticket : numéro d’inventaire, modèle, état des voyants, résultat du contrôle du câble et heure de début.",
                'category' => 'hardware',
                'keywords' => 'ordinateur, alimentation, chargeur, batterie, démarrage, écran noir',
                'is_published' => true,
            ],
            [
                'title' => 'Mon écran externe ou ma station d’accueil n’est pas détecté',
                'slug' => 'ecran-externe-station-accueil-non-detecte',
                'summary' => 'Contrôlez les branchements et l’entrée de l’écran sans forcer les connecteurs ni modifier les paramètres avancés.',
                'content' => "1. Vérifiez que l’écran est allumé et que la source d’entrée sélectionnée correspond au câble utilisé.\n2. Contrôlez visuellement les branchements entre le poste, la station d’accueil et l’écran. Ne forcez jamais un connecteur.\n3. Si la station d’accueil dispose d’une alimentation séparée, vérifiez son branchement habituel.\n4. Déconnectez puis reconnectez une fois le câble vidéo ou la station, si aucun transfert ou travail critique n’est en cours.\n5. Dans les paramètres d’affichage standards de votre système, lancez la détection d’écran si cette option est disponible. N’installez pas de pilote téléchargé hors du catalogue de l’entreprise.\nÀ joindre au ticket : modèle de l’écran ou de la station, câbles utilisés, poste concerné et voyants observés.",
                'category' => 'hardware',
                'keywords' => 'écran, moniteur, station accueil, dock, HDMI, USB-C, affichage',
                'is_published' => true,
            ],
            [
                'title' => 'La synchronisation de mes fichiers professionnels est en pause',
                'slug' => 'synchronisation-fichiers-professionnels-en-pause',
                'summary' => 'Vérifiez la connexion et l’état de l’application officielle sans supprimer de fichiers ni désactiver la synchronisation.',
                'content' => "1. Vérifiez la connexion au réseau professionnel et ouvrez l’application de synchronisation approuvée.\n2. Consultez son état et notez le message ou le nom du fichier en erreur.\n3. Si l’application demande une authentification, utilisez uniquement la fenêtre officielle que vous venez d’ouvrir et ne partagez aucun code.\n4. Ne supprimez pas le dossier synchronisé, ne renommez pas en masse les fichiers et ne désactivez pas la protection de synchronisation. Ces actions peuvent créer des pertes ou des copies en conflit.\n5. Si l’application signale un conflit, un quota dépassé ou une erreur persistante, contactez le support IT avant de déplacer les fichiers.\nÀ joindre au ticket : nom de l’application, heure de la dernière synchronisation réussie, texte de l’erreur et dossier concerné (sans joindre de document confidentiel).",
                'category' => 'software',
                'keywords' => 'synchronisation, fichiers, cloud, partage, conflit, hors ligne',
                'is_published' => true,
            ],
            [
                'title' => 'Je ne reçois pas le code ou la demande de validation multifacteur',
                'slug' => 'validation-multifacteur-code-non-recu',
                'summary' => 'Validez uniquement une connexion que vous avez initiée et signalez immédiatement toute demande inattendue.',
                'content' => "1. Vérifiez que vous venez bien de démarrer la connexion et que l’identifiant affiché correspond à votre compte.\n2. Consultez l’application ou le moyen de validation configuré par l’entreprise. Vérifiez la connexion de l’appareil et attendez quelques instants avant de demander un nouvel envoi.\n3. N’approuvez jamais une demande que vous n’avez pas initiée et ne communiquez aucun code, même à une personne se présentant comme le support.\n4. Si vous avez changé ou perdu votre téléphone, n’essayez pas de contourner la validation. Contactez le support IT par un canal connu pour rétablir votre méthode d’authentification.\n5. Si plusieurs demandes inattendues apparaissent, refusez-les et signalez immédiatement l’incident.\nÀ joindre au ticket : heure, application concernée et type de message reçu. Ne joignez jamais le code lui-même.",
                'category' => 'access',
                'keywords' => 'MFA, authentification multifacteur, code, validation, téléphone, accès',
                'is_published' => true,
            ],
            [
                'title' => 'Un document Word, Excel ou PDF ne s’ouvre pas',
                'slug' => 'document-bureautique-pdf-ne-souvre-pas',
                'summary' => 'Vérifiez le fichier et l’application approuvée sans activer de contenu inconnu ni télécharger de convertisseur.',
                'content' => "1. Vérifiez que le téléchargement ou la copie du fichier est terminé et que le fichier provient d’un emplacement professionnel connu.\n2. Ouvrez l’application approuvée correspondante puis utilisez son menu d’ouverture pour sélectionner le fichier.\n3. Si le document vient d’un courriel inattendu, ne l’ouvrez pas et signalez le message selon la procédure sécurité.\n4. Si une fenêtre demande d’activer des macros, du contenu ou des liens externes, n’acceptez pas sans confirmation de l’expéditeur et des règles internes.\n5. Essayez une seule fois une copie du fichier dans un emplacement professionnel autorisé. Ne téléchargez pas de convertisseur et ne partagez pas le document sur un service public.\nÀ joindre au ticket : type de fichier, application utilisée, origine générale du document et texte exact de l’erreur. N’ajoutez le document que si son partage est autorisé.",
                'category' => 'software',
                'keywords' => 'document, Word, Excel, PDF, fichier corrompu, macros, bureautique',
                'is_published' => true,
            ],
            [
                'title' => 'Mon navigateur affiche une fenêtre ou des notifications inhabituelles',
                'slug' => 'navigateur-fenetres-notifications-inhabituelles',
                'summary' => 'N’autorisez rien et ne téléchargez aucun outil proposé par une fenêtre inattendue.',
                'content' => "1. Ne cliquez pas sur les boutons, liens ou numéros de téléphone affichés dans la fenêtre suspecte.\n2. Fermez l’onglet ou le navigateur avec les commandes habituelles. Si la fenêtre empêche toute action, contactez le support depuis un autre appareil ou par un canal connu.\n3. Ne téléchargez pas de mise à jour, d’antivirus ou d’outil de nettoyage proposé par cette fenêtre.\n4. Si vous avez autorisé des notifications, saisi un mot de passe ou installé un fichier, prévenez immédiatement le support IT ou le référent sécurité. Ne tentez pas de masquer l’incident.\n5. Conservez le texte affiché ou une capture si cela peut être fait sans cliquer ni exposer des données sensibles.\nÀ joindre au signalement : heure, site consulté juste avant l’apparition et action éventuellement réalisée. Masquez toute donnée personnelle visible.",
                'category' => 'security',
                'keywords' => 'navigateur, fenêtre pop-up, notification, logiciel malveillant, alerte, sécurité',
                'is_published' => true,
            ],
            [
                'title' => 'La batterie de mon ordinateur professionnel se décharge trop vite',
                'slug' => 'batterie-ordinateur-se-decharge-rapidement',
                'summary' => 'Vérifiez les usages et l’état visible de la batterie, sans utiliser d’outil de diagnostic non approuvé.',
                'content' => "1. Vérifiez si le problème survient avec une application ou un usage particulier et notez la durée approximative de fonctionnement.\n2. Réduisez la luminosité et fermez les applications non nécessaires, si cela est compatible avec votre activité.\n3. Utilisez le chargeur fourni ou approuvé par l’entreprise et vérifiez qu’il est bien connecté.\n4. Si la batterie gonfle, chauffe anormalement, fuit ou dégage une odeur, cessez d’utiliser et de charger l’appareil. Éloignez-vous de l’appareil si nécessaire et contactez immédiatement le support selon la procédure locale.\n5. N’ouvrez pas le poste et n’installez pas d’outil tiers pour tester la batterie.\nÀ joindre au ticket : modèle ou numéro d’inventaire, âge approximatif du poste si connu, autonomie observée et présence éventuelle d’un voyant ou message.",
                'category' => 'hardware',
                'keywords' => 'batterie, autonomie, charge, ordinateur portable, chauffe, gonflement',
                'is_published' => true,
            ],
            [
                'title' => 'Je souhaite signaler un nouvel équipement ou demander un remplacement',
                'slug' => 'demande-equipement-informatique-remplacement',
                'summary' => 'Décrivez le besoin et faites valider la demande avant tout achat ou échange de matériel.',
                'content' => "1. Décrivez l’équipement nécessaire et l’usage professionnel prévu (poste, écran, casque ou périphérique autorisé).\n2. Faites valider le besoin et le budget par votre responsable selon le processus interne.\n3. Pour un remplacement, indiquez le numéro d’inventaire de l’équipement actuel et expliquez le problème ou la raison du renouvellement.\n4. N’achetez pas de matériel et ne transférez pas de données vers un équipement personnel sans autorisation.\n5. Créez une demande via le formulaire ou le ticket IT prévu. Le support vous indiquera les options compatibles et les modalités de remise de l’ancien matériel.\nÀ joindre à la demande : usage, lieu d’affectation, urgence justifiée et validation requise. Ne publiez pas de numéro de série ou d’information sensible en dehors du ticket.",
                'category' => 'hardware',
                'keywords' => 'équipement, matériel, remplacement, ordinateur, écran, casque, demande',
                'is_published' => true,
            ],
            [
                'title' => 'La date ou l’heure de mon ordinateur est incorrecte',
                'slug' => 'date-heure-ordinateur-incorrecte',
                'summary' => 'Vérifiez le fuseau affiché et le réseau ; ne modifiez pas les réglages administrateur.',
                'content' => "1. Comparez l’heure et le fuseau horaire affichés avec ceux de votre site ou de votre région.\n2. Vérifiez que le poste est connecté au réseau professionnel, puis attendez quelques minutes pour permettre la synchronisation automatique.\n3. Si vous êtes en déplacement, signalez le changement de fuseau à votre support ou suivez les consignes internes pour votre poste.\n4. Ne modifiez pas les paramètres d’heure administrateur et n’installez pas d’outil de synchronisation tiers. Une horloge incorrecte peut affecter la connexion, les certificats et l’horodatage des opérations.\n5. Si l’heure reste incorrecte ou change après chaque redémarrage, ouvrez un ticket IT. Pour un poste lié à un équipement opérationnel, demandez conseil avant toute correction.\nÀ joindre au ticket : heure affichée, heure attendue, site ou fuseau, modèle ou numéro d’inventaire du poste et effet constaté.",
                'category' => 'software',
                'keywords' => 'heure, date, fuseau horaire, synchronisation, connexion, certificat',
                'is_published' => true,
            ],
        ];
    }
}
