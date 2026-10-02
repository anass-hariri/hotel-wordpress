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
	$images = hr_sanitize_id_list( isset( $assoc['images'] ) ? $assoc['images'] : array() );
	$count  = 0;

	// Bâtiment(s) de l'hôtel.
	$sites = array();
	foreach ( array( 'Ti al Lannec' ) as $name ) {
		$term           = term_exists( $name, 'batiment' ) ?: wp_insert_term( $name, 'batiment' );
		$sites[ $name ] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
	}

	// Équipements affichés dans « À propos de cette chambre » (communs à toutes les chambres du Ti al Lannec).
	$common = array(
		'Accès Internet WiFi gratuit',
		'Téléphone',
		'Bouilloire électrique',
		'Coffre-fort',
		'Minibar',
		'Téléviseur avec chaînes satellites',
		'Peignoirs et pantoufles',
		'Sèche-cheveux',
		'Serviettes et tongs pour la piscine',
		'Table et fer à repasser (sur demande)',
	);

	/*
	 * Les 9 hébergements, dans l'ordre d'affichage.
	 * Le titre doit être identique au nom du dossier dans photos/ pour que les photos s'y rattachent.
	 * Colonnes : titre, catégorie, bâtiment, code, personnes, surface min, surface max (0 = unique), vue, literie, extrait.
	 * Valeur vide ('' ou 0) = non affichée sur le site.
	 * Description détaillée (fiche « Découvrir ») : tableau $details plus bas, un paragraphe par ligne.
	 */
	$rooms = array(
		array( 'Chambre Standard', 'chambres', 'Ti al Lannec', 'STD', 2, 24, 30, 'Côté jardin', 'Lit Queen size ou lits jumeaux', 'Situées côté jardin, nos quatre chambres Standard de 24 à 30 m² offrent tout le confort du Ti al Lannec dans une atmosphère paisible.' ),
		array( 'Chambre Tradition', 'chambres', 'Ti al Lannec', 'TRA', 2, 29, 35, 'Côté jardin', 'Lit Queen size ou lits jumeaux', 'Côté jardin, nos cinq chambres Tradition de 29 à 35 m² offrent un cadre chaleureux, avec coin salon et petit bureau.' ),
		array( 'Chambre Classique', 'chambres', 'Ti al Lannec', 'CLA', 2, 23, 34, 'Vue mer ou latérale, balcon', 'Lit Queen size ou lits jumeaux', 'Avec vue sur la mer et balcon ou balcon Juliette, les chambres Classiques existent aussi en version individuelle.' ),
		array( 'Chambre Supérieure', 'chambres', 'Ti al Lannec', 'SUP', 2, 24, 37, 'Vue mer, balcon', 'Lit Queen size ou lits jumeaux', 'Face à la mer, avec balcon ou balcon Juliette, nos huit chambres Supérieures marient tissus fleuris et velours chatoyants.' ),
		array( 'Chambre Terrasse', 'chambres', 'Ti al Lannec', 'TER', 2, 31, 40, 'Vue mer, plein sud', 'Lit Queen size ou lits jumeaux', 'Orientées au sud face à la mer, les chambres Terrasse de 31 à 40 m² se prolongent par une large terrasse.' ),
		array( 'Suite Tradition', 'suites', 'Ti al Lannec', 'STRA', 3, 29, 36, 'Côté jardin', '', 'Situées côté jardin, les deux suites Tradition (29 et 36 m²) sont spécialement pensées pour l’accueil des familles.' ),
		array( 'Suite Supérieure', 'suites', 'Ti al Lannec', 'SSUP', 3, 45, 0, 'Vue mer, coucher de soleil', '', 'Orientée à l’ouest, la suite Supérieure de 45 m² est idéale pour admirer le coucher de soleil sur la mer et les îles.' ),
		array( 'Suite Terrasse', 'suites', 'Ti al Lannec', 'STER', 3, 45, 48, 'Vue mer, plein sud', '', 'Face à la mer côté sud, les deux suites Terrasse de 45 et 48 m² disposent d’une très belle terrasse.' ),
		array( 'Suite Aristide', 'suites', 'Ti al Lannec', 'ARI', 3, 59, 0, 'Vue panoramique sur la mer', '', 'Orientée au sud, la suite Aristide offre 59 m² avec vue panoramique sur la mer et les îles, et une vaste terrasse.' ),
	);

	// Descriptions détaillées affichées sur la fiche (bouton « Découvrir »). Un paragraphe par ligne.
	// Sources : tiallannec.com (pages de chaque catégorie et Tarifs 2026).
	$equip   = 'Chaque chambre dispose d’un accès Internet WiFi gratuit, d’un téléphone, d’une bouilloire électrique, d’un coffre-fort, d’un minibar et d’un téléviseur avec chaînes satellites. Peignoirs et pantoufles, sèche-cheveux, serviettes et tongs pour la piscine sont à votre disposition ; table et fer à repasser sur demande.';
	$extra   = 'Lit supplémentaire et berceau sur demande. Animaux de compagnie acceptés (avec supplément). Petit-déjeuner continental ou buffet en supplément.';
	$famille = 'Séjournez avec vos enfants dans nos suites spécialement pensées pour l’accueil des familles, et profitez de la piscine et des équipements mis à leur disposition.';
	$details = array(
		'Chambre Standard'   => "Les chambres Standard sont situées côté jardin. Leur surface varie de 24 à 30 m² ; nous proposons quatre chambres de ce type.\n$equip\nTarif : de 255 € à 340 € la nuit pour deux personnes selon la saison (hors taxe de séjour).\n$extra",
		'Chambre Tradition'  => "Les chambres Tradition sont situées côté jardin. Leur surface varie de 29 à 35 m² ; nous proposons cinq chambres de ce type.\nDes tissus fleuris aux velours chatoyants, elles offrent un coin salon, un petit bureau et une table pour savourer votre petit-déjeuner.\n$equip\nTarif : de 295 € à 380 € la nuit pour deux personnes selon la saison (hors taxe de séjour).\n$extra",
		'Chambre Classique'  => "Les chambres Classiques offrent une vue sur la mer avec balcon ou balcon Juliette. Côté sud, trois chambres de 23 à 25 m² ; côté est, une chambre de 34 m² avec vue mer latérale.\nElles sont proposées en chambre double ou en chambre individuelle.\n$equip\nTarif : de 320 € à 405 € la nuit pour deux personnes ; de 210 € à 255 € en chambre individuelle, selon la saison (hors taxe de séjour).\n$extra",
		'Chambre Supérieure' => "Les chambres Supérieures offrent une vue sur la mer avec balcon ou balcon Juliette. Nous en proposons huit : sept côté sud, de 24 à 30 m² (dont une avec terrasse privative), et une côté ouest de 37 m².\n$equip\nTarif : de 380 € à 465 € la nuit pour deux personnes selon la saison (hors taxe de séjour).\n$extra",
		'Chambre Terrasse'   => "Orientées au sud face à la mer, les chambres Terrasse offrent de 31 à 40 m² de confort intérieur, prolongés par une large terrasse.\n$equip\nTarif : de 430 € à 515 € la nuit pour deux personnes selon la saison (hors taxe de séjour).\n$extra",
		'Suite Tradition'    => "$famille\nLes suites Tradition sont situées côté jardin. L’une d’entre elles a une superficie de 36 m² et la deuxième de 29 m².\n$equip\nTarif : de 315 € à 470 € la nuit selon la saison (hors taxe de séjour).\n$extra",
		'Suite Supérieure'   => "$famille\nD’une surface de 45 m² avec balcons Juliette, la suite Supérieure est orientée à l’ouest : elle est idéale pour admirer le coucher de soleil sur la mer et les îles.\n$equip\nTarif : de 395 € à 550 € la nuit selon la saison (hors taxe de séjour).\n$extra",
		'Suite Terrasse'     => "$famille\nFace à la mer côté sud, les deux suites Terrasse mesurent 45 et 48 m² et disposent d’une très belle terrasse.\n$equip\nTarif : de 465 € à 620 € la nuit selon la saison (hors taxe de séjour).\n$extra",
		'Suite Aristide'     => "$famille\nLa suite Aristide, orientée au sud, offre 59 m² de confort intérieur avec vue panoramique sur la mer et les îles, ainsi qu’une vaste terrasse.\n$equip\nTarif : de 560 € à 690 € la nuit selon la saison (hors taxe de séjour).\n$extra",
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
				WP_CLI::log( 'Erreur : ' . $id->get_error_message() );
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
		$count++;
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
	WP_CLI::success( sprintf( '%d hébergement(s) prêt(s). Lancez ensuite « wp hotel-rooms photos » pour les photos.', $count ) );
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