<?php

return [
    'reset' => 'Votre mot de passe a été réinitialisé.',
    'sent' => 'Un lien de réinitialisation vous a été envoyé par e-mail.',
    'throttled' => 'Merci de patienter avant de retenter.',
    'token' => 'Ce jeton de réinitialisation est invalide.',

    /*
     * Message rendu dans le cas ou un compte est absent. Il ne doit pas etre
     * juge sur sa lisibilite : la demande de lien repond pareil pour un compte
     * inconnu (censure des adresses employees), donc ce texte ne sort pas du
     * formulaire de demande de lien.
     */
    'user' => 'Aucun compte ne correspond à cette adresse e-mail.',
];
