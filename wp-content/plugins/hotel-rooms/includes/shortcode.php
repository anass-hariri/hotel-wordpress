<?php
/**
 * Page listing : shortcode [chambres_suites] et bloc Gutenberg « Chambres & Suites ».
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'chambres_suites', 'hr_listing_shortcode' );

/**
 * ===== TEXTES PAR DÉFAUT DE L'EN-TÊTE : À MODIFIER ICI =====
 * Utilisés quand le bloc ou le shortcode ne précise pas son propre titre ou sa propre introduction.
 * Les *astérisques* mettent un mot en italique.
 */
function hr_default_texts() {
	return array(
		'title' => 'Vue magique, vie *idyllique*',
		'intro' => 'Les 111 chambres et suites de l’Hôtel du Cap-Eden-Roc sont situées sur trois sites différents : l’Hôtel du Cap, correspondant au bâtiment historique, le pavillon Eden-Roc, surplombant la mer Méditerranée, et la discrète et intime résidence Les Deux Fontaines.',
	);
}

/**
 * Attributs :
 *  titre     Titre de section. Le texte entre *astérisques* est mis en italique.
 *  intro     Paragraphe d'introduction.
 *  filtres   1/0 : afficher les onglets Voir tout / Chambres / Suites.
 *  categorie Slug(s) de type_hebergement pour restreindre (ex. « suites »).
 *  batiment  Slug(s) de bâtiment pour restreindre.
 *  nombre    Nombre maximum d'hébergements (-1 = tous).
 *  fil       1/0 : afficher le fil d'Ariane « Accueil > Titre de la page ».
 */
function hr_listing_shortcode( $atts ) {
	$defaults = hr_default_texts();
	$atts     = shortcode_atts( array(
		'titre'     => $defaults['title'],
		'intro'     => $defaults['intro'],
		'filtres'   => '1',
		'categorie' => '',
		'batiment'  => '',
		'nombre'    => -1,
		'fil'       => '1',
	), $atts, 'chambres_suites' );

	return hr_render_listing( array(
		'title'    => $atts['titre'],
		'intro'    => $atts['intro'],
		'filters'  => filter_var( $atts['filtres'], FILTER_VALIDATE_BOOLEAN ),
		'category' => $atts['categorie'],
		'site'     => $atts['batiment'],
		'limit'    => (int) $atts['nombre'],
		'breadcrumb' => filter_var( $atts['fil'], FILTER_VALIDATE_BOOLEAN ),
	) );
}

function hr_render_listing( $args ) {
	$args = wp_parse_args( $args, array(
		'title'    => '',
		'intro'    => '',
		'filters'  => true,
		'category' => '',
		'site'     => '',
		'limit'    => -1,
		'breadcrumb' => true,
	) );

	hr_enqueue_front();

	$tax_query = array();
	if ( $args['category'] ) {
		$tax_query[] = array( 'taxonomy' => 'type_hebergement', 'field' => 'slug', 'terms' => array_map( 'sanitize_title', explode( ',', $args['category'] ) ) );
	}
	if ( $args['site'] ) {
		$tax_query[] = array( 'taxonomy' => 'batiment', 'field' => 'slug', 'terms' => array_map( 'sanitize_title', explode( ',', $args['site'] ) ) );
	}

	$query = new WP_Query( array(
		'post_type'      => 'chambre',
		'post_status'    => 'publish',
		'posts_per_page' => $args['limit'] > 0 ? $args['limit'] : 100,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
		'no_found_rows'  => true,
	) );

	$rooms = array_map( 'hr_get_room', $query->posts );

	// Onglets : uniquement les catégories réellement présentes dans les résultats.
	$terms = array();
	if ( $args['filters'] && ! $args['category'] ) {
		$present = array();
		foreach ( $rooms as $room ) {
			$present = array_merge( $present, $room['types'] );
		}
		$all = get_terms( array( 'taxonomy' => 'type_hebergement', 'hide_empty' => true ) );
		if ( ! is_wp_error( $all ) ) {
			foreach ( $all as $term ) {
				if ( in_array( $term->slug, $present, true ) ) {
					$terms[] = $term;
				}
			}
		}
	}

	// Filtre actif via ?type= : fonctionne sans JavaScript (rendu côté serveur).
	$active = isset( $_GET['type'] ) ? sanitize_title( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $active && ! in_array( $active, wp_list_pluck( $terms, 'slug' ), true ) ) {
		$active = '';
	}

	$uid = wp_unique_id( 'hr-listing-' );

	ob_start();
	?>
	<section class="hr-listing" id="<?php echo esc_attr( $uid ); ?>" data-active="<?php echo esc_attr( $active ); ?>">
		<?php if ( $args['title'] || $args['intro'] || $args['breadcrumb'] ) : ?>
			<header class="hr-listing__head">
				<?php if ( $args['breadcrumb'] ) : ?>
					<?php echo hr_render_breadcrumb(); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
				<?php endif; ?>
				<?php if ( $args['title'] ) : ?>
					<h1 class="hr-listing__title"><?php echo hr_emphasis( $args['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans hr_emphasis. ?></h1>
				<?php endif; ?>
				<?php if ( $args['intro'] ) : ?>
					<p class="hr-listing__intro"><?php echo esc_html( $args['intro'] ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<?php if ( count( $terms ) > 1 ) : ?>
			<nav class="hr-filters" aria-label="<?php esc_attr_e( 'Filtrer les hébergements', 'hotel-rooms' ); ?>">
				<ul>
					<li><a href="<?php echo esc_url( remove_query_arg( 'type' ) ); ?>" data-filter="" <?php echo $active ? '' : 'aria-current="true"'; ?>><?php esc_html_e( 'Voir tout', 'hotel-rooms' ); ?></a></li>
					<?php foreach ( $terms as $term ) : ?>
						<li><a href="<?php echo esc_url( add_query_arg( 'type', $term->slug ) ); ?>" data-filter="<?php echo esc_attr( $term->slug ); ?>" <?php echo $active === $term->slug ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $term->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<?php if ( $rooms ) : ?>
			<div class="hr-grid">
				<?php
				foreach ( $rooms as $i => $room ) {
					$hidden = $active && ! in_array( $active, $room['types'], true );
					$card   = hr_render_card( $room, 'h3', $i < 2 );
					if ( $hidden ) {
						$card = preg_replace( '/^(\s*<article\b)/', '$1 hidden', $card, 1 );
					}
					echo $card; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans hr_render_card.
				}
				?>
			</div>
			<p class="hr-listing__status screen-reader-text" aria-live="polite"></p>
		<?php else : ?>
			<p class="hr-empty"><?php esc_html_e( 'Aucun hébergement pour le moment.', 'hotel-rooms' ); ?></p>
		<?php endif; ?>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Fil d'Ariane « Accueil > Page courante », avec données structurées schema.org.
 */
function hr_render_breadcrumb() {
	$current = is_singular() ? get_the_title() : wp_get_document_title();
	$items   = array(
		array( 'name' => __( 'Accueil', 'hotel-rooms' ), 'url' => home_url( '/' ) ),
		array( 'name' => $current, 'url' => '' ),
	);

	$html = '<nav class="hr-breadcrumb" aria-label="' . esc_attr__( 'Fil d’Ariane', 'hotel-rooms' ) . '"><ol>';
	$ld   = array();
	foreach ( $items as $i => $item ) {
		$html .= '<li>';
		if ( $item['url'] ) {
			$html .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a>';
		} else {
			$html .= '<span aria-current="page">' . esc_html( $item['name'] ) . '</span>';
		}
		$html .= '</li>';
		$ld[]  = array_filter( array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['name'],
			'item'     => $item['url'] ?: get_permalink(),
		) );
	}
	$html .= '</ol></nav>';

	$html .= '<script type="application/ld+json">' . wp_json_encode( array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $ld,
	), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script>';

	return $html;
}

/*
 * Sur la page qui contient le listing, le titre affiché par le thème fait doublon
 * avec le grand titre du listing : on le masque. Désactivable avec :
 * add_filter( 'hr_hide_page_title', '__return_false' );
 */
add_filter( 'body_class', function ( $classes ) {
	$post = get_post();
	if ( is_singular() && $post && apply_filters( 'hr_hide_page_title', true )
		&& ( has_shortcode( $post->post_content, 'chambres_suites' ) || has_block( 'hotel-rooms/listing', $post ) ) ) {
		$classes[] = 'hr-has-listing';
	}
	return $classes;
} );

/**
 * Échappe le texte puis convertit *mot* en <em>mot</em>.
 */
function hr_emphasis( $text ) {
	return preg_replace( '/\*([^*]+)\*/', '<em>$1</em>', esc_html( $text ) );
}

/* Bloc dynamique, sans étape de build : rendu côté serveur, aperçu via ServerSideRender. */
add_action( 'init', function () {
	wp_register_script(
		'hr-block-editor',
		HR_URL . 'assets/block.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		HR_VERSION,
		true
	);

	register_block_type( 'hotel-rooms/listing', array(
		'api_version'     => 3,
		'title'           => __( 'Chambres & Suites', 'hotel-rooms' ),
		'category'        => 'widgets',
		'icon'            => 'building',
		'editor_script'   => 'hr-block-editor',
		'editor_style'    => 'hr-front',
		'supports'        => array(
			'align'   => array( 'wide', 'full' ),
			'html'    => false,
			// Panneau « Styles » du bloc : couleur de fond et de texte, marges internes.
			'color'   => array( 'background' => true, 'text' => true ),
			'spacing' => array( 'padding' => true ),
		),
		'attributes'      => array(
			'title'    => array( 'type' => 'string', 'default' => hr_default_texts()['title'] ),
			'intro'    => array( 'type' => 'string', 'default' => hr_default_texts()['intro'] ),
			'filters'  => array( 'type' => 'boolean', 'default' => true ),
			'category' => array( 'type' => 'string', 'default' => '' ),
			'limit'    => array( 'type' => 'number', 'default' => -1 ),
			'breadcrumb' => array( 'type' => 'boolean', 'default' => true ),
			'align'    => array( 'type' => 'string' ),
			'backgroundColor' => array( 'type' => 'string' ),
			'textColor'       => array( 'type' => 'string' ),
			'style'           => array( 'type' => 'object' ),
		),
		'render_callback' => function ( $attributes ) {
			$wrapper = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes() : '';
			return '<div ' . $wrapper . '>' . hr_render_listing( $attributes ) . '</div>';
		},
	) );
} );