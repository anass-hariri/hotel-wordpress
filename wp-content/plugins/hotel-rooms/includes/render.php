<?php
/**
 * Fonctions de rendu partagées : carte, carrousel, caractéristiques, liens de réservation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	// Polices du grand titre et des textes (Google Fonts). Désactivable : add_filter( 'hr_load_fonts', '__return_false' );
	$deps = array();
	if ( apply_filters( 'hr_load_fonts', true ) ) {
		wp_register_style( 'hr-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;1,300&family=Oranienbaum&family=Marcellus&family=Lato:wght@300;400;700&display=swap', array(), null );
		$deps[] = 'hr-fonts';
	}
	wp_register_style( 'hr-front', HR_URL . 'assets/front.css', $deps, HR_VERSION );
	wp_add_inline_style( 'hr-front', ':root{--hr-accent:' . esc_attr( hr_get_setting( 'accent' ) ) . ';}' );
	wp_register_script( 'hr-front', HR_URL . 'assets/front.js', array(), HR_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/* Couleur de fond appliquée à tout le site (réglable dans Chambres & Suites > Réglages). */
add_action( 'wp_enqueue_scripts', function () {
	$bg = sanitize_hex_color( (string) hr_get_setting( 'site_bg' ) );
	if ( ! $bg ) {
		return;
	}
	wp_register_style( 'hr-site', false, array(), HR_VERSION );
	wp_enqueue_style( 'hr-site' );
	wp_add_inline_style( 'hr-site', sprintf(
		'html body,html body .wp-site-blocks{background-color:%1$s}:root{--hr-site-bg:%1$s;--wp--preset--color--base:%1$s}',
		$bg
	) );
}, 20 );

/* Chargement dans le <head> quand la page en a besoin (évite un flash sans style). */
add_action( 'wp_enqueue_scripts', function () {
	$post = get_post();
	if ( is_singular( 'chambre' )
		|| ( is_singular() && $post && ( has_shortcode( $post->post_content, 'chambres_suites' ) || has_block( 'hotel-rooms/listing', $post ) ) ) ) {
		hr_enqueue_front();
	}
} );

function hr_enqueue_front() {
	wp_enqueue_style( 'hr-front' );
	wp_enqueue_script( 'hr-front' );
}

/**
 * « 32 m² (344 pi²) » ou « 32-39 m² (344-420 pi²) ».
 */
function hr_format_surface( $surface, $with_sqft = true ) {
	list( $min, $max ) = array_map( 'intval', $surface );
	if ( ! $min && ! $max ) {
		return '';
	}
	if ( ! $min ) {
		$min = $max;
		$max = 0;
	}
	$m2  = ( $max && $max !== $min ) ? $min . '-' . $max : (string) $min;
	$out = sprintf( __( '%s m²', 'hotel-rooms' ), $m2 );

	if ( $with_sqft ) {
		$sq  = function ( $v ) {
			return (int) round( $v * 10.7639 );
		};
		$ft  = ( $max && $max !== $min ) ? $sq( $min ) . '-' . $sq( $max ) : (string) $sq( $min );
		$out .= ' ' . sprintf( __( '(%s pi²)', 'hotel-rooms' ), $ft );
	}
	return $out;
}

/**
 * URL de réservation pour un code chambre, ou chaîne vide si non configurée.
 */
function hr_booking_url( $code ) {
	$base = (string) hr_get_setting( 'booking_url' );
	if ( '' === $base ) {
		return '';
	}
	if ( false !== strpos( $base, '{code}' ) ) {
		return str_replace( '{code}', rawurlencode( $code ), $base );
	}
	return $code ? add_query_arg( 'room', rawurlencode( $code ), $base ) : $base;
}

function hr_booking_link( $room, $class = 'hr-btn hr-btn--solid', $label = '' ) {
	$label  = $label ?: __( 'Réserver', 'hotel-rooms' );
	$url    = hr_booking_url( $room['code'] );
	$target = hr_get_setting( 'booking_new_tab' ) ? ' target="_blank" rel="noopener"' : '';

	if ( ! $url ) {
		// Pas de moteur de réservation : page de demande ou e-mail, si réglés dans les Réglages.
		$target = '';
		$page   = (int) hr_get_setting( 'booking_page' );
		$email  = sanitize_email( (string) hr_get_setting( 'booking_email' ) );
		if ( $page && 'publish' === get_post_status( $page ) ) {
			$url = add_query_arg( 'chambre', rawurlencode( $room['title'] ), get_permalink( $page ) );
		} elseif ( $email ) {
			/* translators: %s : nom de l'hébergement */
			$url = 'mailto:' . $email . '?subject=' . rawurlencode( sprintf( __( 'Demande de réservation : %s', 'hotel-rooms' ), $room['title'] ) );
		}
	}

	// Aucune destination : bouton purement visuel, sans lien.
	if ( ! $url ) {
		return sprintf(
			'<span class="%1$s hr-btn--static"><span>%2$s</span>%3$s</span>',
			esc_attr( $class ),
			esc_html( $label ),
			hr_icon( 'chevron' )
		);
	}

	return sprintf(
		'<a class="%1$s" href="%2$s"%3$s aria-label="%4$s"><span>%5$s</span>' . hr_icon( 'chevron' ) . '</a>',
		esc_attr( $class ),
		esc_url( $url, array( 'http', 'https', 'mailto' ) ),
		$target,
		/* translators: %s : nom de l'hébergement */
		esc_attr( sprintf( __( 'Réserver : %s', 'hotel-rooms' ), $room['title'] ) ),
		esc_html( $label )
	);
}

function hr_icon( $name ) {
	$icons = array(
		'guests'  => '<circle cx="12" cy="7.5" r="3.5"/><path d="M5 20.5v-1.5a5.5 5.5 0 0 1 5.5-5.5h3a5.5 5.5 0 0 1 5.5 5.5v1.5"/>',
		'surface' => '<path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5M4 4l5 5M20 4l-5 5M20 20l-5-5M4 20l5-5"/>',
		'view'    => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'bed'     => '<path d="M2 18V6M2 14h20v4M22 14v-2a3 3 0 0 0-3-3h-8v5M6.5 11.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/>',
		'prev'    => '<path d="m15 18-6-6 6-6"/>',
		'next'    => '<path d="m9 18 6-6-6-6"/>',
		'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'chevron' => '<path d="m9.5 7 5 5-5 5"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	return '<svg class="hr-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>';
}

/**
 * Liste des caractéristiques (personnes, surface, vue, literie).
 */
function hr_render_features( $room, $include_bed = false, $compact = false ) {
	$items = array();
	if ( $room['capacity'] ) {
		$items['guests'] = $compact
			/* translators: %d : nombre de personnes */
			? sprintf( _n( '%d personne', '%d personnes', $room['capacity'], 'hotel-rooms' ), $room['capacity'] )
			: sprintf( _n( 'Jusqu’à %d personne', 'Jusqu’à %d personnes', $room['capacity'], 'hotel-rooms' ), $room['capacity'] );
	}
	$surface = hr_format_surface( $room['surface'] );
	if ( $surface ) {
		$items['surface'] = $surface;
	}
	if ( $room['view'] ) {
		$items['view'] = $room['view'];
	}
	if ( $include_bed && $room['bed'] ) {
		$items['bed'] = $room['bed'];
	}
	if ( ! $items ) {
		return '';
	}

	$html = '<ul class="hr-features' . ( $compact ? ' hr-features--inline' : '' ) . '">';
	foreach ( $items as $icon => $label ) {
		$html .= '<li>' . hr_icon( $icon ) . '<span>' . esc_html( $label ) . '</span></li>';
	}
	return $html . '</ul>';
}

/**
 * Carrousel accessible (scroll-snap + boutons). Fonctionne sans JS en défilement tactile.
 */
function hr_render_slider( $ids, $size, $title, $link = '', $eager = false ) {
	$ids = array_values( array_filter( (array) $ids ) );
	if ( ! $ids ) {
		return '<div class="hr-slider hr-slider--empty" aria-hidden="true"></div>';
	}

	$slides = '';
	foreach ( $ids as $i => $id ) {
		$img = wp_get_attachment_image( $id, $size, false, array(
			'loading'  => ( $eager && 0 === $i ) ? 'eager' : 'lazy',
			'decoding' => 'async',
			'sizes'    => 'full' === $size ? '100vw' : '(min-width: 1025px) 33vw, (min-width: 641px) 50vw, 100vw',
			'alt'      => get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: $title,
		) );
		if ( $link && 0 === $i ) {
			$img = '<a href="' . esc_url( $link ) . '" tabindex="-1">' . $img . '</a>';
		}
		$slides .= '<li class="hr-slide">' . $img . '</li>';
	}

	$controls = '';
	if ( count( $ids ) > 1 ) {
		$dots = '';
		foreach ( $ids as $i => $id ) {
			$dots .= sprintf(
				'<button type="button" class="hr-slider__dot" data-index="%1$d" aria-label="%2$s"%3$s></button>',
				$i,
				/* translators: %d : numéro de la photo */
				esc_attr( sprintf( __( 'Photo %d', 'hotel-rooms' ), $i + 1 ) ),
				0 === $i ? ' aria-current="true"' : ''
			);
		}
		$controls = sprintf(
			'<div class="hr-slider__nav" hidden>'
			. '<button type="button" class="hr-slider__btn hr-slider__btn--prev" aria-label="%1$s">%2$s</button>'
			. '<button type="button" class="hr-slider__btn hr-slider__btn--next" aria-label="%3$s">%4$s</button>'
			. '</div>'
			. '<div class="hr-slider__dots">%5$s</div>',
			esc_attr__( 'Image précédente', 'hotel-rooms' ),
			hr_icon( 'prev' ),
			esc_attr__( 'Image suivante', 'hotel-rooms' ),
			hr_icon( 'next' ),
			$dots
		);
	}

	return sprintf(
		'<div class="hr-slider" role="region" aria-roledescription="carrousel" aria-label="%1$s"><ul class="hr-slider__track">%2$s</ul>%3$s</div>',
		/* translators: %s : nom de l'hébergement */
		esc_attr( sprintf( __( 'Photos : %s', 'hotel-rooms' ), $title ) ),
		$slides,
		$controls
	);
}

/**
 * Carte d'un hébergement pour le listing.
 */
function hr_render_card( $room, $heading = 'h2', $eager = false ) {
	$heading = in_array( $heading, array( 'h2', 'h3', 'h4' ), true ) ? $heading : 'h2';

	ob_start();
	?>
	<article class="hr-card" data-types="<?php echo esc_attr( implode( ' ', $room['types'] ) ); ?>">
		<div class="hr-card__media">
			<?php echo hr_render_slider( $room['gallery'], 'large', $room['title'], $room['url'], $eager ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction. ?>
		</div>
		<div class="hr-card__body">
			<<?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput -- liste blanche. ?> class="hr-card__title">
				<a href="<?php echo esc_url( $room['url'] ); ?>"><?php echo esc_html( $room['title'] ); ?></a>
			</<?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php if ( $room['excerpt'] ) : ?>
				<p class="hr-card__excerpt"><?php echo esc_html( $room['excerpt'] ); ?></p>
			<?php endif; ?>
			<?php echo hr_render_features( $room, false, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="hr-card__actions">
				<?php echo hr_booking_link( $room ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<a class="hr-link" href="<?php echo esc_url( $room['url'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Découvrir : %s', 'hotel-rooms' ), $room['title'] ) ); ?>">
					<span><?php esc_html_e( 'Découvrir', 'hotel-rooms' ); ?></span><?php echo hr_icon( 'chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}