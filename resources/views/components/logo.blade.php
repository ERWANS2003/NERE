{{--
    Embleme NERE, seul bloc du logo lisible en petit : le fichier de marque
    complet (logo-nere-mining.png, 628 x 126) associe cet embleme carre a une
    signature or de 314 x 29 px, separee par 187 px de blanc. Afficher l'ensemble
    dans une barre de 32 px de haut transformerait ce blanc en trou visible ;
    l'embleme est donc utilise ici, et le nom de l'application est rendu en
    texte a cote.

    Les attributs width/height reprennent la taille reelle du fichier : le
    navigateur reserve le bon rectangle avant le chargement, la mise en page ne
    saute pas. La taille n'est pas imposee ici : la hauteur seule est fixee par
    l'appelant, la largeur restant automatique, ce qui preserve le rapport de
    127 x 126 sans deformation.
--}}
<img {{ $attributes->merge(['class' => 'shrink-0 object-contain']) }}
     src="{{ asset('images/logo-nere-embleme.png') }}"
     width="127"
     height="126"
     alt=""
     aria-hidden="true">
