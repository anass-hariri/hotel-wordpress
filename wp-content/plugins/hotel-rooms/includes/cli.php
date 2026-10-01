<?php
/**
 * WP-CLI : génère un jeu de démonstration.
 *
 *   wp hotel-rooms seed
 *       Crée (ou met à jour, si elles existent déjà) les 8 chambres et suites + la page listing.
 *       Les descriptions et caractéristiques du tableau $rooms remplacent celles en place.
 *   wp hotel-rooms seed --images=12,13,14,15,16,17   (IDs de médias existants, pour les chambres sans photos)
 *   wp hotel-rooms photos [--par-chambre=3]
 *       Importe les photos du dossier photos/ du plugin et les répartit sur les hébergements.
 *   wp hotel-rooms photos "C:\autre\dossier"   (dossier au choix)
 *   --sans-creation : ne crée pas les chambres manquantes à partir des sous-dossiers
 *   --nettoyer      : met à la corbeille les chambres qui n'ont pas de sous-dossier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

WP_CLI::add_command( 'hotel-rooms seed', function ( $args, $assoc ) {
	$images = isset( $assoc['images'] ) ? hr_sanitize_id_list( $assoc['images'] ) : array();

	// Bâtiments de l'hôtel.
	$sites = array();
	foreach ( array( 'Hôtel du Cap', 'Pavillon Eden-Roc', 'Les Deux Fontaines' ) as $name ) {
		$term           = term_exists( $name, 'batiment' ) ?: wp_insert_term( $name, 'batiment' );
		$sites[ $name ] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
	}

	// Équipements affichés dans « À propos de cette chambre » (modifiables ici ou chambre par chambre dans l'administration).
	$common = array( 'Climatisation', 'Internet sans fil', 'Baignoire et douche séparées', 'Non-fumeur', 'Chambres communicantes (disponible sur demande)' );

	/*
	 * Les 8 hébergements, dans l'ordre d'affichage.
	 * Le titre doit être identique au nom du dossier dans photos/ pour que les photos s'y rattachent.
	 * Colonnes : titre, catégorie, bâtiment, code, personnes, surface min, surface max (0 = unique), vue, literie, extrait.
	 * Description détaillée (fiche « Découvrir ») : tableau $details plus bas, un paragraphe par ligne.
	 */
	$rooms = array(
		array( 'Chambre Tradition, Deux Fontaines', 'chambres', 'Les Deux Fontaines', 'TRAF', 2, 32, 0, 'Vue sur cour intérieure', 'Lit King size', 'Pure invitation à la détente, les Chambres Tradition sont idéalement situées au sein de la résidence Les Deux Fontaines.' ),
		array( 'Chambre Classique, Deux Fontaines', 'chambres', 'Les Deux Fontaines', 'CLAF', 3, 32, 39, 'Vue sur cour intérieure', 'Lit King size ou lits jumeaux', 'D’une superficie de 25 mètres carrés, les Chambres Classiques Deux Fontaines allient confort et élégance contemporaine.' ),
		array( 'Chambre Classique', 'chambres', 'Hôtel du Cap', 'CLA', 2, 25, 30, 'Vue jardin', 'Lit King size ou lits jumeaux', 'Comme une invitation à la détente, les Chambres Classiques s’ouvrent vers le parc de l’hôtel.' ),
		array( 'Chambre Supérieure', 'chambres', 'Hôtel du Cap', 'SUP', 3, 35, 0, 'Vue jardin', 'Lit King size', 'Situées au sein du bâtiment historique de l’Hôtel du Cap, les Chambres Supérieures sont ponctuées de détails raffinés.' ),
		array( 'Junior Suite', 'suites', 'Hôtel du Cap', 'JSU', 3, 45, 50, 'Vue jardin', 'Lit King size', 'Fraîchement rénovées et s’étendant sur 40 à 50 m², les Junior Suites séduisent par leur confort et leurs vues imprenables sur le parc depuis le bâtiment historique de l’Hôtel du Cap.' ),
		array( 'Junior Suite Deluxe', 'suites', 'Hôtel du Cap', 'JSDX', 3, 45, 55, 'Vue parc ou mer à distance', 'Lit King size', 'Spacieuses et raffinées, les Junior Suites Deluxe offrent des vues imprenables depuis le bâtiment historique de l’Hôtel du Cap. Certaines sont dotées d’une charmante terrasse ensoleillée.' ),
		array( 'Junior Suite Eden Roc', 'suites', 'Pavillon Eden-Roc', 'ER', 3, 49, 0, 'Vue mer', 'Lit King size', 'Disposant d’une terrasse face à la mer, les Junior Suites Eden-Roc se dévoilent à travers un mariage de confort moderne et de raffinement.' ),
		array( 'Suite', 'suites', 'Hôtel du Cap', '1BSS', 3, 50, 0, 'Vue jardin', 'Lit King size', 'Fraîchement rénovées, les Suites une Chambre sont situées dans le bâtiment historique de l’Hôtel du Cap.' ),
	);

	// Descriptions détaillées affichées sur la fiche (bouton « Découvrir »). Un paragraphe par ligne.
	$details = array(
		'Chambre Tradition, Deux Fontaines' => "Ajoutée en 1980 pour offrir aux clients un séjour plus intimiste, la Résidence Les Deux Fontaines s'étend sur deux étages et propose 32 chambres lumineuses et aérées, de tailles variées, avec vue sur la cour tranquille ou le jardin.
Les chambres Tradition Deux Fontaines, d’une superficie de 32m², reflètent le style classique français, avec un coin salon confortable et un bureau.
Les salles de bains, revêtues de marbre italien et équipées d’une baignoire ainsi que d’une douche séparée, offrent un généreux assortiment de produits de bain de luxe et incarnent le glamour absolu.
Des fruits frais et des roses parfumées vous attendent à votre arrivée.",
		'Chambre Classique, Deux Fontaines' => "Les Deux Fontaines a été ajoutée en 1980 pour offrir aux clients un séjour plus intimiste à l’Hôtel du Cap-Eden-Roc. Les chambres Classique Deux Fontaines, d’une superficie de 25 m², reprennent le style français traditionnel, revisité avec soin pour le XXIᵉ siècle.
Les chambres Classique Deux Fontaines peuvent accueillir jusqu’à trois personnes et être communicantes avec une chambre séparée, ce qui les rend idéales pour les familles.",
		'Chambre Classique'                 => "Les chambres Classique sont situées dans ce qui fut à l’origine la Villa Soleil du XIXᵉ siècle, aujourd’hui le bâtiment principal de l’Hôtel du Cap-Eden-Roc.
D’une superficie de 25 à 30 m², elles sont décorées dans de doux tons de vert et de bleu, en harmonie avec la vue enchanteresse sur le paysage naturel. Le mobilier de style classique français allie élégance et confort, tandis que les salles de bains spacieuses, revêtues de marbre, incarnent le glamour absolu.
F. Scott Fitzgerald, ancien client, décrivait l’Hôtel du Cap-Eden-Roc comme une « échappatoire au monde entier ». Les chambres Classique, calmes et raffinées, vous invitent à vivre cette même expérience.",
		'Chambre Supérieure'                => "D’une superficie généreuse de 35 m², les chambres Supérieures bénéficient d’un emplacement privilégié dans le bâtiment historique principal de l’Hôtel du Cap-Eden-Roc, anciennement la Villa Soleil du XIXᵉ siècle.
Ces chambres uniques débordent de charme Riviera, avec un mobilier coloré et des imprimés contemporains sur fond de tons doux crème, bleu et vert.
Chaque chambre, spacieuse et confortable, peut accueillir jusqu’à trois personnes",
		'Junior Suite'                      => "Les lustres scintillants, présents dans la chambre comme dans le salon de chaque Junior Suite, donnent le ton : la Riviera dans toute sa splendeur.
Situées dans le bâtiment historique principal de l’Hôtel du Cap-Eden-Roc, autrefois Villa Soleil du XIXᵉ siècle, ces suites généreuses de 45 à 50 m² marient mobilier d’époque en acajou poli, estampes anciennes, canapé moderne et tapis contemporains.
Un grand bureau évoque la génération perdue d’écrivains, tels qu’Ernest Hemingway et F. Scott Fitzgerald, fidèles à l’hôtel.
Pouvant accueillir jusqu’à trois personnes, les Junior Suites offrent une vue magique sur les jardins.",
		'Junior Suite Deluxe'               => "L’Hôtel du Cap-Eden-Roc a vu le jour à la fin du XIXᵉ siècle sous le nom de Villa Soleil.
D’une superficie de 75m² dans le bâtiment principal de l’hôtel, les Suites Deluxe Une Chambre sont une expression fraîche et élégante de l’histoire de l’hôtel.
Décorées dans des tons pastel doux avec des touches florales et des œuvres d’art originales, ces suites offrent une élégance à grande échelle avec un salon séparé ; certaines disposent également d’un balcon.
Avec deux salles de bains luxueuses, les Suites Deluxe peuvent accueillir confortablement jusqu’à trois personnes.",
		'Junior Suite Eden Roc'             => "Perchées au-dessus de la mer, à quelques pas du bâtiment principal, les Eden-Roc Junior Suites sont situées dans le Pavillon Eden-Roc, autrefois un restaurant dont le menu fut illustré par Pablo Picasso. Aujourd’hui, ce lieu emblématique abrite certaines des plus belles suites de l’hôtel, dont les Eden-Roc Junior Suites de 49 m².
Lumineuses et aérées, elles disposent d’un salon élégant s’ouvrant sur une terrasse privée avec vue envoûtante sur la Méditerranée calme et azurée, jusqu’aux îles de Lérins.
Pouvant accueillir jusqu’à trois personnes, ces suites sont également disponibles en version communicante pour les séjours en famille.",
		'Suite'                             => "Occupant 50 m² d’espace magnifiquement conçu, les Suites une chambre disposent d’une grande chambre et d’un salon séparé, mêlant un mobilier Louis XV soigneusement choisi à des pièces modernes.
De beaux tissus de maisons historiques, des estampes encadrées contemporaines et anciennes, ainsi que des lustres en cristal sont baignés de lumière naturelle, tandis que les fenêtres élégamment habillées offrent une vue envoûtante sur le domaine de l’hôtel.
Tout est à portée de main : après un délicieux petit-déjeuner au Restaurant Eden-Roc, flânez le long de La Grande Allée jusqu’à la célèbre piscine d’eau salée de l’hôtel, qui inspira le roman de F. Scott Fitzgerald en 1934, Tender is the Night.",
	);

	$per = $images ? max( 1, (int) floor( count( $images ) / 2 ) ) : 0;

	foreach ( $rooms as $i => $r ) {
		list( $title, $type, $site, $code, $cap, $smin, $smax, $view, $bed, $excerpt ) = $r;

		// Chambre existante (par exemple créée par l'import des photos) : on complète ses informations.
		$existing = get_page_by_path( sanitize_title( $title ), OBJECT, 'chambre' );
		if ( $existing ) {
			$id     = $existing->ID;
			// La description du tableau ci-dessus est la référence : elle remplace l'extrait existant.
			$update = array( 'ID' => $id, 'menu_order' => $i, 'post_excerpt' => $excerpt );
			$plain  = trim( wp_strip_all_tags( $existing->post_content ) );
			if ( '' === $plain || false !== strpos( $plain, 'Chaque détail a été pensé pour le repos' ) ) {
				$update['post_content'] = "<!-- wp:paragraph -->\n<p>" . esc_html( $excerpt ) . "</p>\n<!-- /wp:paragraph -->";
			}
			wp_update_post( $update );
			$label = 'Mis à jour';
		} else {
			$id = wp_insert_post( array(
				'post_type'    => 'chambre',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => sanitize_title( $title ),
				'post_excerpt' => $excerpt,
				'post_content' => "<!-- wp:paragraph -->\n<p>" . esc_html( $excerpt ) . "</p>\n<!-- /wp:paragraph -->",
				'menu_order'   => $i,
			), true );

			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( $id->get_error_message() );
				continue;
			}
			$label = 'Créé';
		}

		wp_set_object_terms( $id, $type, 'type_hebergement' );
		wp_set_object_terms( $id, $sites[ $site ], 'batiment' );
		update_post_meta( $id, '_hr_code', $code );
		update_post_meta( $id, '_hr_capacity', $cap );
		update_post_meta( $id, '_hr_surface_min', $smin );
		update_post_meta( $id, '_hr_surface_max', $smax );
		update_post_meta( $id, '_hr_view', $view );
		update_post_meta( $id, '_hr_bed', $bed );
		update_post_meta( $id, '_hr_amenities', $common );
		if ( ! empty( $details[ $title ] ) ) {
			update_post_meta( $id, '_hr_details', $details[ $title ] );
		}

		// Photos par IDs (--images) seulement si la chambre n'en a pas encore.
		if ( $images && ! get_post_meta( $id, '_hr_gallery', true ) ) {
			$gallery = array();
			for ( $k = 0; $k < min( $per, 4 ); $k++ ) {
				$gallery[] = $images[ ( $i * 2 + $k ) % count( $images ) ];
			}
			set_post_thumbnail( $id, $gallery[0] );
			update_post_meta( $id, '_hr_gallery', array_values( array_unique( $gallery ) ) );
		}

		WP_CLI::log( "$label : $title (#$id)" );
	}

	$page = get_page_by_path( 'chambres-et-suites' );
	if ( ! $page ) {
		$page_id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Chambres & Suites',
			'post_name'    => 'chambres-et-suites',
			'post_content' => '<!-- wp:hotel-rooms/listing {"align":"wide"} /-->', // textes : voir hr_default_texts() dans shortcode.php
		) );
		$settings                 = (array) get_option( 'hr_settings', array() );
		$settings['listing_page'] = $page_id;
		update_option( 'hr_settings', $settings );
		WP_CLI::log( 'Page listing créée : ' . get_permalink( $page_id ) );
	}

	flush_rewrite_rules();
	WP_CLI::success( 'Hébergements prêts. Lancez ensuite « wp hotel-rooms photos » pour les photos.' );
} );

/**
 * Importe les photos et les répartit sur les hébergements.
 * Sans argument : utilise le dossier photos/ du plugin (recommandé, il part avec le site à l'hébergement).
 */
WP_CLI::add_command( 'hotel-rooms photos', function ( $args, $assoc ) {
	$dir = isset( $args[0] ) ? rtrim( $args[0], '/\\' ) : hr_photos_dir();
	if ( ! is_dir( $dir ) ) {
		WP_CLI::error( "Dossier introuvable : $dir" );
	}
	WP_CLI::log( "Dossier : $dir" );

	$create = ! isset( $assoc['sans-creation'] );
	$prune  = isset( $assoc['nettoyer'] );
	$result = hr_import_photos( $dir, isset( $assoc['par-chambre'] ) ? (int) $assoc['par-chambre'] : 3, array( 'WP_CLI', 'log' ), $create, $prune );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	foreach ( $result['errors'] as $error ) {
		WP_CLI::warning( $error );
	}
	WP_CLI::success( sprintf( '%d chambre(s) créée(s), %d descriptif(s), %d photo(s) importée(s), %d chambre(s) mise(s) à la corbeille.', $result['created'], $result['described'], $result['imported'], $result['trashed'] ) );
} );