<?php
/**
 * Pied de page commun à tout le site : coordonnées, réservation, réseaux sociaux, signature.
 * Remplace le pied de page du thème (activé avec l'en-tête : Chambres & Suites > Réglages).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ===== CONTENU DU PIED DE PAGE : À MODIFIER ICI =====
 * Laissez une valeur vide ('') pour masquer l'élément correspondant.
 */
function hr_default_footer() {
	return array(
		'hotel'    => array(
			'title'   => 'Ti al Lannec Hôtel, Restaurant & Spa',
			'address' => '14, allée de Mezo Guen, 22560 Trébeurden, France',
			'phone'   => '+33 (0)2 96 15 01 01',
			'map_url' => 'https://maps.google.com/maps?q=48.7691592,-3.5794508',
			'map'     => 'Ouvrir la carte',
		),
		'booking'  => array(
			'title'   => 'Réservation d’un séjour',
			'email'   => 'contact@tiallannec.com',
			'phone'   => '+33 (0)2 96 15 01 01',
			'contact' => 'Nous contacter', // Lien vers la page WordPress « Contact » si elle existe.
		),
		// Réseaux sociaux : laissez l'adresse vide pour masquer un réseau.
		'social'   => array(
			'TikTok'    => '',
			'Instagram' => 'https://www.instagram.com/ti_al_lannec_hotel_trebeurden/',
			'YouTube'   => '',
			'Facebook'  => 'https://www.facebook.com/TI.AL.LANNEC.Hotel/',
			'LinkedIn'  => '',
			'WeChat'    => '', // ex. lien de votre compte officiel
			'Weibo'     => '', // ex. https://weibo.com/votrecompte
		),
		'brand'    => 'Ti al Lannec',
		'tagline'  => 'Hôtel ★★★★ · Restaurant & Spa · Ouvert d’avril à octobre',
	);
}

add_filter( 'body_class', function ( $classes ) {
	if ( function_exists( 'hr_header_enabled' ) && hr_header_enabled() ) {
		$classes[] = 'hr-has-footer';
	}
	return $classes;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'hr_header_enabled' ) || ! hr_header_enabled() ) {
		return;
	}
	// Icônes des réseaux sociaux : Font Awesome Free (licence CC BY 4.0 pour les icônes), version « marques ».
	wp_enqueue_style( 'hr-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/fontawesome.min.css', array(), '6.5.2' );
	wp_enqueue_style( 'hr-fontawesome-brands', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/brands.min.css', array( 'hr-fontawesome' ), '6.5.2' );
	wp_enqueue_style( 'hr-footer', HR_URL . 'assets/footer.css', array( 'hr-header' ), HR_VERSION );
}, 16 );

/**
 * Icônes des réseaux sociaux (Font Awesome Free, famille « brands »).
 * Pour un autre réseau, ajoutez son nom et sa classe : https://fontawesome.com/search?ic=brands
 */
function hr_social_icon( $name ) {
	$icons = array(
		'TikTok'    => 'fa-tiktok',
		'Instagram' => 'fa-instagram',
		'YouTube'   => 'fa-youtube',
		'Facebook'  => 'fa-facebook-f',
		'LinkedIn'  => 'fa-linkedin',
		'WeChat'    => 'fa-weixin',
		'Weibo'     => 'fa-weibo',
		'X'         => 'fa-x-twitter',
		'Pinterest' => 'fa-pinterest-p',
	);
	if ( isset( $icons[ $name ] ) ) {
		return '<i class="fa-brands ' . esc_attr( $icons[ $name ] ) . '" aria-hidden="true"></i>';
	}
	return '<span class="hr-footer__social-text" aria-hidden="true">' . esc_html( $name ) . '</span>';
}

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'hr_header_enabled' ) || ! hr_header_enabled() ) {
		return;
	}
	$f       = hr_default_footer();
	$contact = function_exists( 'hr_menu_url' ) ? hr_menu_url( 'Contact' ) : '#';
	$tel     = function ( $phone ) {
		return 'tel:' . preg_replace( '/[^0-9+]/', '', str_replace( '(0)', '', $phone ) );
	};
	$chev    = hr_icon( 'chevron' );
	?>
	<footer class="hr-footer" role="contentinfo">
		<div class="hr-footer__cols">
			<section class="hr-footer__col">
				<div class="hr-footer__text">
					<h2 class="hr-footer__title"><?php echo esc_html( $f['hotel']['title'] ); ?></h2>
					<?php if ( $f['hotel']['address'] ) : ?>
						<p><?php echo esc_html( $f['hotel']['address'] ); ?></p>
					<?php endif; ?>
					<?php if ( $f['hotel']['phone'] ) : ?>
						<p><a href="<?php echo esc_attr( $tel( $f['hotel']['phone'] ) ); ?>"><?php echo esc_html( $f['hotel']['phone'] ); ?></a></p>
					<?php endif; ?>
				</div>
				<?php if ( $f['hotel']['map_url'] ) : ?>
					<a class="hr-footer__link" href="<?php echo esc_url( $f['hotel']['map_url'] ); ?>" target="_blank" rel="noopener"><span><?php echo esc_html( $f['hotel']['map'] ); ?></span><?php echo $chev; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
			</section>

			<section class="hr-footer__col">
				<div class="hr-footer__text">
					<h2 class="hr-footer__title"><?php echo esc_html( $f['booking']['title'] ); ?></h2>
					<p>
						<?php esc_html_e( 'Vous pouvez contacter notre équipe', 'hotel-rooms' ); ?>
						<?php if ( $f['booking']['email'] ) : ?>
							<?php esc_html_e( 'par email à', 'hotel-rooms' ); ?>
							<a href="<?php echo esc_attr( 'mailto:' . antispambot( $f['booking']['email'] ) ); ?>"><?php echo esc_html( antispambot( $f['booking']['email'] ) ); ?></a>
						<?php endif; ?>
						<?php if ( $f['booking']['email'] && $f['booking']['phone'] ) : ?>
							<?php esc_html_e( 'ou', 'hotel-rooms' ); ?>
						<?php endif; ?>
						<?php if ( $f['booking']['phone'] ) : ?>
							<?php esc_html_e( 'par téléphone au', 'hotel-rooms' ); ?>
							<a class="hr-footer__plain" href="<?php echo esc_attr( $tel( $f['booking']['phone'] ) ); ?>"><?php echo esc_html( $f['booking']['phone'] ); ?></a>.
						<?php endif; ?>
					</p>
				</div>
				<?php if ( $f['booking']['contact'] ) : ?>
					<?php if ( '#' !== $contact ) : ?>
						<a class="hr-footer__link" href="<?php echo esc_url( $contact ); ?>"><span><?php echo esc_html( $f['booking']['contact'] ); ?></span><?php echo $chev; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php else : ?>
						<span class="hr-footer__link"><span><?php echo esc_html( $f['booking']['contact'] ); ?></span><?php echo $chev; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</section>
		</div>

		<?php $social = array_filter( $f['social'] ); ?>
		<?php if ( $social ) : ?>
			<ul class="hr-footer__social">
				<?php foreach ( $social as $name => $url ) : ?>
					<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $name ); ?>" title="<?php echo esc_attr( $name ); ?>"><?php echo hr_social_icon( $name ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $f['brand'] ) : ?>
			<div class="hr-footer__brand">
				<p class="hr-footer__brand-name"><?php echo esc_html( $f['brand'] ); ?></p>
				<?php if ( $f['tagline'] ) : ?>
					<p class="hr-footer__brand-tagline"><?php echo esc_html( $f['tagline'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</footer>
	<?php
}, 5 );