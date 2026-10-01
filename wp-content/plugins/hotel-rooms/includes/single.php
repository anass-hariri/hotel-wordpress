<?php
/**
 * Fiche détaillée : injectée dans the_content pour rester compatible
 * avec tous les thèmes (classiques et thèmes blocs / FSE).
 * Un thème peut reprendre la main en fournissant single-chambre.php et en
 * désactivant l'injection : add_filter( 'hr_enhance_single', '__return_false' );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'hr_single_content', 20 );

/* Classe sur <body> : sert à masquer le titre et l'image du thème, remplacés par la fiche. */
add_filter( 'body_class', function ( $classes ) {
	if ( is_singular( 'chambre' ) && apply_filters( 'hr_enhance_single', true ) ) {
		$classes[] = 'hr-room-page';
	}
	return $classes;
} );

function hr_single_content( $content ) {
	if ( ! is_singular( 'chambre' ) || ! in_the_loop() || ! is_main_query() || ! apply_filters( 'hr_enhance_single', true ) ) {
		return $content;
	}

	// Évite une récursion si un bloc imbriqué rappelle the_content.
	remove_filter( 'the_content', 'hr_single_content', 20 );

	$room    = hr_get_room( get_the_ID() );
	$listing = (int) hr_get_setting( 'listing_page' );
	$count   = count( $room['gallery'] );
	hr_enqueue_front();

	ob_start();
	?>
	<div class="hr-room alignwide">
		<header class="hr-room__head">
			<nav class="hr-breadcrumb" aria-label="<?php esc_attr_e( 'Fil d’Ariane', 'hotel-rooms' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Accueil', 'hotel-rooms' ); ?></a></li>
					<?php if ( $listing ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $listing ) ); ?>"><?php echo esc_html( get_the_title( $listing ) ); ?></a></li>
					<?php endif; ?>
					<li><span aria-current="page"><?php echo esc_html( $room['title'] ); ?></span></li>
				</ol>
			</nav>
			<h1 class="hr-room__title"><?php echo esc_html( $room['title'] ); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="hr-room__lead"><?php echo esc_html( $room['excerpt'] ); ?></p>
			<?php endif; ?>
			<div class="hr-room__cta">
				<?php echo hr_booking_link( $room, 'hr-btn hr-btn--solid hr-btn--lg', __( 'Réserver cette chambre', 'hotel-rooms' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<nav class="hr-room__tabs" aria-label="<?php esc_attr_e( 'Sections', 'hotel-rooms' ); ?>">
				<a href="#hr-a-propos" aria-current="true"><?php esc_html_e( 'À propos', 'hotel-rooms' ); ?></a>
				<?php if ( $count > 0 ) : ?>
					<button type="button" data-hr-open="hr-gallery-<?php echo (int) $room['id']; ?>"><?php esc_html_e( 'Galerie', 'hotel-rooms' ); ?></button>
				<?php endif; ?>
			</nav>
		</header>

		<?php if ( $room['gallery'] ) : ?>
			<div class="hr-room__hero">
				<?php echo hr_render_slider( $room['gallery'], 'full', $room['title'], '', true ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
			</div>
			<button type="button" class="hr-link hr-room__gallery-btn" data-hr-open="hr-gallery-<?php echo (int) $room['id']; ?>">
				<span>
					<?php
					/* translators: %d : nombre de photos */
					echo esc_html( sprintf( __( 'Voir la galerie (%d)', 'hotel-rooms' ), $count ) );
					?>
				</span><?php echo hr_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
		<?php endif; ?>

		<div class="hr-room__body" id="hr-a-propos">
			<div class="hr-room__text">
				<?php
				// Ordre de priorité : description détaillée, puis contenu de l'éditeur, puis résumé.
				if ( '' !== trim( $room['details'] ) ) {
					echo hr_paragraphs( $room['details'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction.
				} elseif ( trim( wp_strip_all_tags( $content ) ) ) {
					echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- contenu déjà filtré par WordPress.
				} else {
					echo '<p>' . esc_html( $room['excerpt'] ) . '</p>';
				}
				?>
				<div class="hr-room__cta hr-room__cta--left">
					<?php echo hr_booking_link( $room, 'hr-btn hr-btn--solid hr-btn--lg', __( 'Réserver cette chambre', 'hotel-rooms' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<aside class="hr-room__specs" aria-label="<?php esc_attr_e( 'Caractéristiques', 'hotel-rooms' ); ?>">
				<?php echo hr_render_key_features( $room ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</aside>
		</div>

		<?php if ( $room['amenities'] ) : ?>
			<section class="hr-room__about">
				<h2 class="hr-room__about-title"><?php esc_html_e( 'à propos de cette chambre', 'hotel-rooms' ); ?></h2>
				<div class="hr-room__about-list">
					<h3 class="hr-room__h3"><?php esc_html_e( 'Équipements', 'hotel-rooms' ); ?></h3>
					<ul class="hr-amenities">
						<?php foreach ( $room['amenities'] as $item ) : ?>
							<li><?php echo hr_amenity_label( $item ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<?php echo hr_render_related( $room ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="hr-room__stickybook">
			<?php echo hr_booking_link( $room, 'hr-btn hr-btn--solid hr-room__stickybook-btn', __( 'Réservez votre séjour', 'hotel-rooms' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<?php if ( $count > 0 ) : ?>
			<dialog class="hr-lightbox" id="hr-gallery-<?php echo (int) $room['id']; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Photos : %s', 'hotel-rooms' ), $room['title'] ) ); ?>">
				<button type="button" class="hr-lightbox__close" data-hr-close aria-label="<?php esc_attr_e( 'Fermer', 'hotel-rooms' ); ?>">×</button>
				<?php echo hr_render_slider( $room['gallery'], 'full', $room['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</dialog>
		<?php endif; ?>
	</div>
	<?php
	$html = ob_get_clean();

	add_filter( 'the_content', 'hr_single_content', 20 );
	return $html;
}

/**
 * Texte brut → paragraphes HTML (un paragraphe par ligne non vide).
 */
function hr_paragraphs( $text ) {
	$html = '';
	foreach ( preg_split( '/\R+/u', trim( $text ) ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line && '*' !== $line ) {
			$html .= '<p>' . esc_html( $line ) . '</p>';
		}
	}
	return $html;
}

/**
 * Équipement : le texte entre parenthèses s'affiche en petit italique dessous,
 * ex. « Chambres communicantes (disponible sur demande) ».
 */
function hr_amenity_label( $item ) {
	if ( preg_match( '/^(.*?)\s*\((.+)\)\s*$/u', $item, $m ) ) {
		return esc_html( $m[1] ) . '<em>' . esc_html( $m[2] ) . '</em>';
	}
	return esc_html( $item );
}

/**
 * Liste des caractéristiques avec icônes : personnes, literie, surface, vue.
 */
function hr_render_key_features( $room ) {
	$items = array();
	if ( $room['capacity'] ) {
		/* translators: %d : nombre de personnes */
		$items['guests'] = sprintf( _n( '%d personne', '%d personnes', $room['capacity'], 'hotel-rooms' ), $room['capacity'] );
	}
	if ( $room['bed'] ) {
		$items['bed'] = $room['bed'];
	}
	$surface = hr_format_surface( $room['surface'] );
	if ( $surface ) {
		$items['surface'] = $surface;
	}
	if ( $room['view'] ) {
		$items['view'] = $room['view'];
	}
	if ( ! $items ) {
		return '';
	}
	$html = '<ul class="hr-keyfeatures">';
	foreach ( $items as $icon => $label ) {
		$html .= '<li>' . hr_icon( $icon ) . '<span>' . esc_html( $label ) . '</span></li>';
	}
	return $html . '</ul>';
}

/**
 * « Nous vous suggérons aussi » : trois autres hébergements, dans l'ordre d'affichage.
 */
function hr_render_related( $room ) {
	$posts = get_posts( array(
		'post_type'      => 'chambre',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'post__not_in'   => array( $room['id'] ),
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	) );
	if ( ! $posts ) {
		return '';
	}

	$html = '<section class="hr-related"><h2 class="hr-room__h2">' . esc_html__( 'Nous vous suggérons également', 'hotel-rooms' ) . '</h2><div class="hr-grid hr-grid--related">';
	foreach ( $posts as $post ) {
		$html .= hr_render_card( hr_get_room( $post ), 'h3' );
	}
	return $html . '</div></section>';
}

/* La galerie remplace l'image à la une affichée par le thème (évite le doublon). */
add_filter( 'post_thumbnail_html', function ( $html, $post_id ) {
	if ( is_singular( 'chambre' ) && (int) $post_id === get_queried_object_id() && ! is_admin() && apply_filters( 'hr_enhance_single', true ) && ! doing_filter( 'the_content' ) ) {
		return '';
	}
	return $html;
}, 10, 2 );

/* Données structurées schema.org pour les fiches. */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'chambre' ) ) {
		return;
	}
	$room = hr_get_room( get_queried_object_id() );
	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'HotelRoom',
		'name'        => $room['title'],
		'description' => $room['excerpt'],
		'url'         => $room['url'],
		'occupancy'   => array( '@type' => 'QuantitativeValue', 'maxValue' => $room['capacity'] ),
	);
	if ( $room['surface'][0] ) {
		$data['floorSize'] = array( '@type' => 'QuantitativeValue', 'value' => $room['surface'][0], 'unitCode' => 'MTK' );
	}
	if ( $room['bed'] ) {
		$data['bed'] = $room['bed'];
	}
	if ( $room['amenities'] ) {
		$data['amenityFeature'] = array_map( function ( $name ) {
			return array( '@type' => 'LocationFeatureSpecification', 'name' => $name, 'value' => true );
		}, $room['amenities'] );
	}
	if ( $room['gallery'] ) {
		$data['image'] = array_values( array_filter( array_map( function ( $id ) {
			return wp_get_attachment_image_url( $id, 'large' );
		}, $room['gallery'] ) ) );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . "</script>\n";
} );