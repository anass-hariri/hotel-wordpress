<?php
/**
 * En-tête commun à tout le site : menu (hamburger) à gauche, nom de l'hôtel au centre,
 * bouton « Réserver » à droite. Remplace l'en-tête du thème.
 * Réglages : Chambres & Suites > Réglages (nom, sous-titre, activation).
 * Menu : Apparence > Menus, emplacement « Menu de l’en-tête de l’hôtel ».
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'hr-header'           => __( 'Menu de l’en-tête de l’hôtel (grands liens)', 'hotel-rooms' ),
		'hr-header-secondary' => __( 'Menu de l’en-tête de l’hôtel (petits liens)', 'hotel-rooms' ),
	) );
} );

/**
 * ===== MENU PAR DÉFAUT : À MODIFIER ICI =====
 * Utilisé tant qu'aucun menu n'est choisi dans Apparence > Menus.
 * Chaque lien pointe vers la page WordPress du même titre si elle existe, sinon nulle part (#).
 * 'children' = sous-menu (s'ouvre avec la flèche ›).
 */
function hr_default_menu() {
	return array(
		'main'      => array(
			array( 'title' => 'L’Hôtel', 'children' => array( 'Présentation', 'Services', 'Bar et salons', 'Histoire' ) ),
			array( 'title' => 'Chambres & Suites', 'listing' => true ),
			array( 'title' => 'Séjour', 'children' => array( 'Accueil enfants', 'Offres spéciales' ) ),
			array( 'title' => 'Restaurants & Bar', 'children' => array( 'Restaurant', '« Ti » Lounge', 'Bar' ) ),
			array( 'title' => 'Spa Thalgo', 'children' => array( 'Spa L’Espace Bleu Marine', 'Bien-être & Fitness', 'Boutique et cadeaux', 'Le Spa en images' ) ),
			array( 'title' => 'Loisirs', 'children' => array( 'Piscine panoramique', 'Jardins & terrasses', 'Activités', 'Environs' ) ),
			array( 'title' => 'Événements', 'children' => array( 'Réunions & séminaires' ) ),
			array( 'title' => 'Galerie' ),
		),
		'secondary' => array( 'Accès', 'Brochures', 'Tarifs', 'Contact', 'Carrières' ),
		'language'  => 'FR',
		'band'      => array(
			'title' => 'Coffrets cadeaux',
			'text'  => 'Offrez un séjour, un dîner ou un soin au spa',
			'url'   => '',
		),
	);
}

/**
 * URL d'une page d'après son titre (ou « # » si elle n'existe pas encore).
 */
function hr_menu_url( $title, $listing = false ) {
	if ( $listing && (int) hr_get_setting( 'listing_page' ) ) {
		return get_permalink( (int) hr_get_setting( 'listing_page' ) );
	}
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'title'          => wp_specialchars_decode( $title ),
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	) );
	return $pages ? get_permalink( $pages[0] ) : '#';
}

/**
 * Arbre de liens d'un emplacement de menu WordPress (2 niveaux), ou null s'il n'est pas défini.
 */
function hr_menu_tree( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return null;
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		return null;
	}
	$tree = array();
	foreach ( $items as $item ) {
		if ( ! $item->menu_item_parent ) {
			$tree[ $item->ID ] = array( 'title' => $item->title, 'url' => $item->url, 'children' => array() );
		}
	}
	foreach ( $items as $item ) {
		if ( $item->menu_item_parent && isset( $tree[ $item->menu_item_parent ] ) ) {
			$tree[ $item->menu_item_parent ]['children'][] = array( 'title' => $item->title, 'url' => $item->url );
		}
	}
	return array_values( $tree );
}

function hr_header_enabled() {
	return ! is_admin() && (bool) hr_get_setting( 'header_enabled' ) && apply_filters( 'hr_header_enabled', true );
}

add_filter( 'body_class', function ( $classes ) {
	if ( hr_header_enabled() ) {
		$classes[] = 'hr-has-header';
	}
	return $classes;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! hr_header_enabled() ) {
		return;
	}
	$deps = wp_style_is( 'hr-fonts', 'registered' ) ? array( 'hr-fonts' ) : array();
	wp_enqueue_style( 'hr-header', HR_URL . 'assets/header.css', $deps, HR_VERSION );
	wp_add_inline_style( 'hr-header', ':root{--hr-accent:' . esc_attr( hr_get_setting( 'accent' ) ) . ';}' );
	wp_enqueue_script( 'hr-header', HR_URL . 'assets/header.js', array(), HR_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
}, 15 );

/**
 * Lien « Réserver » général (sans chambre précise).
 */
function hr_header_booking_link( $class = 'hr-header__book', $label = '' ) {
	$label  = esc_html( $label ?: __( 'Réserver', 'hotel-rooms' ) );
	$chev   = hr_icon( 'chevron' );
	$url    = (string) hr_get_setting( 'booking_url' );
	$target = '';

	if ( $url ) {
		$url    = remove_query_arg( 'room', str_replace( '{code}', '', $url ) );
		$target = hr_get_setting( 'booking_new_tab' ) ? ' target="_blank" rel="noopener"' : '';
	} elseif ( (int) hr_get_setting( 'booking_page' ) && 'publish' === get_post_status( (int) hr_get_setting( 'booking_page' ) ) ) {
		$url = get_permalink( (int) hr_get_setting( 'booking_page' ) );
	} elseif ( sanitize_email( (string) hr_get_setting( 'booking_email' ) ) ) {
		$url = 'mailto:' . sanitize_email( (string) hr_get_setting( 'booking_email' ) ) . '?subject=' . rawurlencode( __( 'Demande de réservation', 'hotel-rooms' ) );
	}

	if ( ! $url ) {
		return '<span class="' . esc_attr( $class ) . ' hr-btn--static"><span>' . $label . '</span>' . $chev . '</span>';
	}
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url, array( 'http', 'https', 'mailto' ) ) . '"' . $target . '><span>' . $label . '</span>' . $chev . '</a>';
}

/**
 * Contenu du panneau : grands liens (avec sous-menus), petits liens, langue, bandeau.
 */
function hr_header_menu() {
	$default = hr_default_menu();

	// Grands liens.
	$main = hr_menu_tree( 'hr-header' );
	if ( null === $main ) {
		$main = array();
		foreach ( $default['main'] as $item ) {
			$children = array();
			foreach ( isset( $item['children'] ) ? $item['children'] : array() as $child ) {
				$children[] = array( 'title' => $child, 'url' => hr_menu_url( $child ) );
			}
			$main[] = array( 'title' => $item['title'], 'url' => hr_menu_url( $item['title'], ! empty( $item['listing'] ) ), 'children' => $children );
		}
	}

	// Petits liens.
	$secondary = hr_menu_tree( 'hr-header-secondary' );
	if ( null === $secondary ) {
		$secondary = array();
		foreach ( $default['secondary'] as $title ) {
			$secondary[] = array( 'title' => $title, 'url' => hr_menu_url( $title ) );
		}
	}

	$current = untrailingslashit( home_url( add_query_arg( array() ) ) );
	$html    = '<div class="hr-drawer__view" data-hr-view="main"><ul class="hr-drawer__menu">';
	$subs    = '';
	foreach ( $main as $i => $item ) {
		$is_current = untrailingslashit( $item['url'] ) === $current ? ' aria-current="page"' : '';
		if ( $item['children'] ) {
			$id    = 'hr-sub-' . $i;
			$html .= '<li><button type="button" class="hr-drawer__parent" data-hr-sub="' . esc_attr( $id ) . '" aria-expanded="false"><span>' . esc_html( $item['title'] ) . '</span>' . hr_icon( 'chevron' ) . '</button></li>';
			$subs .= '<div class="hr-drawer__view" data-hr-view="' . esc_attr( $id ) . '" hidden>';
			$subs .= '<button type="button" class="hr-drawer__back" data-hr-back>' . hr_icon( 'prev' ) . '<span>' . esc_html__( 'Retour', 'hotel-rooms' ) . '</span></button>';
			$subs .= '<p class="hr-drawer__subtitle">' . esc_html( $item['title'] ) . '</p>';
			$subs .= '<ul class="hr-drawer__submenu">';
			foreach ( $item['children'] as $child ) {
				$subs .= '<li><a href="' . esc_url( $child['url'] ) . '">' . esc_html( $child['title'] ) . '</a></li>';
			}
			$subs .= '</ul>';
			/* translators: %s : nom de la rubrique, ex. « Restaurants & Bars » */
			$discover = '<span>' . esc_html( sprintf( __( 'Découvrir les %s', 'hotel-rooms' ), $item['title'] ) ) . '</span>' . hr_icon( 'chevron' );
			$subs    .= '#' !== $item['url']
				? '<a class="hr-drawer__discover" href="' . esc_url( $item['url'] ) . '">' . $discover . '</a>'
				: '<span class="hr-drawer__discover">' . $discover . '</span>';
			$subs .= '</div>';
		} else {
			$html .= '<li><a href="' . esc_url( $item['url'] ) . '"' . $is_current . '>' . esc_html( $item['title'] ) . '</a></li>';
		}
	}
	$html .= '</ul>';

	// Bouton de réservation : visible dans le menu uniquement sur téléphone (il quitte alors l'en-tête).
	$html .= '<div class="hr-drawer__book">' . hr_header_booking_link( 'hr-drawer__book-btn', __( 'Réservez votre séjour', 'hotel-rooms' ) ) . '</div>';

	$html .= '<ul class="hr-drawer__secondary">';
	foreach ( $secondary as $item ) {
		$html .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a></li>';
	}
	if ( $default['language'] ) {
		$html .= '<li class="hr-drawer__lang"><span>' . esc_html( $default['language'] ) . '</span><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></li>';
	}
	$html .= '</ul></div>';

	return array( $html, $subs );
}

/**
 * Bandeau beige en bas du panneau.
 */
function hr_header_band() {
	$band = hr_default_menu()['band'];
	if ( empty( $band['title'] ) ) {
		return '';
	}
	$inner = '<span class="hr-drawer__band-text"><strong>' . esc_html( $band['title'] ) . '</strong><span>' . esc_html( $band['text'] ) . '</span></span>' . hr_icon( 'chevron' );
	return $band['url']
		? '<a class="hr-drawer__band" href="' . esc_url( $band['url'] ) . '">' . $inner . '</a>'
		: '<div class="hr-drawer__band">' . $inner . '</div>';
}

add_action( 'wp_body_open', function () {
	if ( ! hr_header_enabled() ) {
		return;
	}
	// Nom et sous-titre : Réglages en priorité, sinon les valeurs par défaut ci-dessous.
	$title    = trim( (string) hr_get_setting( 'header_title' ) ) ?: 'Ti al Lannec';
	$subtitle = trim( (string) hr_get_setting( 'header_subtitle' ) ) ?: 'Trébeurden · Côte de Granit Rose';
	?>
	<header class="hr-header" role="banner">
		<div class="hr-header__inner">
			<button type="button" class="hr-header__burger" aria-controls="hr-drawer" aria-expanded="false" aria-label="<?php esc_attr_e( 'Ouvrir le menu', 'hotel-rooms' ); ?>">
				<span></span><span></span><span></span>
			</button>

			<a class="hr-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="hr-header__title"><?php echo esc_html( $title ); ?></span>
				<?php if ( $subtitle ) : ?>
					<span class="hr-header__subtitle"><?php echo esc_html( $subtitle ); ?></span>
				<?php endif; ?>
			</a>

			<?php echo hr_header_booking_link(); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
		</div>
	</header>

	<?php $menu = hr_header_menu(); ?>
	<div class="hr-drawer" id="hr-drawer" hidden>
		<div class="hr-drawer__overlay" data-hr-drawer-close></div>
		<nav class="hr-drawer__panel" aria-label="<?php esc_attr_e( 'Menu principal', 'hotel-rooms' ); ?>">
			<div class="hr-drawer__top">
				<a class="hr-drawer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Accueil', 'hotel-rooms' ); ?>">
					<?php
					if ( has_custom_logo() ) {
						echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'thumbnail' );
					} elseif ( has_site_icon() ) {
						echo '<img src="' . esc_url( get_site_icon_url( 96 ) ) . '" alt="" width="36" height="36">';
					}
					?>
				</a>
				<button type="button" class="hr-drawer__close" data-hr-drawer-close aria-label="<?php esc_attr_e( 'Fermer le menu', 'hotel-rooms' ); ?>">
					<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M4 4l16 16M20 4 4 20"/></svg>
				</button>
			</div>
			<div class="hr-drawer__body">
				<?php echo $menu[0]; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans hr_header_menu(). ?>
				<?php echo hr_header_band(); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
			</div>
		</nav>
		<?php if ( $menu[1] ) : ?>
			<div class="hr-drawer__side" hidden>
				<button type="button" class="hr-drawer__close hr-drawer__close--side" data-hr-drawer-close aria-label="<?php esc_attr_e( 'Fermer le menu', 'hotel-rooms' ); ?>">
					<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M4 4l16 16M20 4 4 20"/></svg>
				</button>
				<?php echo $menu[1]; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans hr_header_menu(). ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}, 5 );