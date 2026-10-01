=== Hotel Rooms Showcase ===
Requires at least: 6.0
Requires PHP: 7.4
License: GPL-2.0-or-later

Page « Chambres & Suites » pour hôtel : type de contenu dédié, galeries, filtres, fiches détaillées.

== Installation ==
1. Zipper le dossier hotel-rooms/ puis Extensions > Ajouter > Téléverser (ou copier dans wp-content/plugins/).
2. Activer. Les catégories « Chambres » et « Suites » sont créées automatiquement.
3. Chambres & Suites > Réglages : URL du moteur de réservation (jeton {code}), page listing, couleur d'accent.
4. Créer une page et insérer le bloc « Chambres & Suites » ou le shortcode :
   [chambres_suites titre="Vue magique, vie *idyllique*" intro="..." filtres="1"]
5. Réglages > Permaliens > Enregistrer (régénère les URL /hebergements/chambres-et-suites/...).

Démo : wp hotel-rooms seed --images=12,13,14  (IDs de médias existants)

== Saisie d'un hébergement ==
Titre, résumé (carte du listing), contenu (fiche), image à la une, catégorie, bâtiment, ordre.
Caractéristiques : code chambre, capacité, surface min/max (pi² calculés), vue, literie, équipements.
Galerie : médiathèque, glisser-déposer pour l'ordre.

== Shortcode ==
titre      Texte entre *astérisques* en italique
intro      Paragraphe d'introduction
filtres    1 / 0
categorie  slug(s) de catégorie, ex. suites
batiment   slug(s) de bâtiment
nombre     maximum (-1 = tous)

== Personnalisation ==
Variables CSS : --hr-accent, --hr-serif, --hr-radius, --hr-gap...
Désactiver la fiche injectée (pour un single-chambre.php maison) :
  add_filter( 'hr_enhance_single', '__return_false' );
Métadonnées exposées dans l'API REST (/wp-json/wp/v2/chambre).
