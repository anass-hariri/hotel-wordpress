<?php
/**
 * Plugin Name:       Hotel Rooms Showcase
 * Description:       Chambres & Suites : type de contenu dédié, caractéristiques, galeries, page listing filtrable et fiches détaillées.
 * Version:           1.0.32
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hariri
 * License:           GPL-2.0-or-later
 * Text Domain:       hotel-rooms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HR_VERSION', '1.0.32' );
define( 'HR_FILE', __FILE__ );
define( 'HR_PATH', plugin_dir_path( __FILE__ ) );
define( 'HR_URL', plugin_dir_url( __FILE__ ) );

require_once HR_PATH . 'includes/settings.php';
require_once HR_PATH . 'includes/post-types.php';
require_once HR_PATH . 'includes/meta-boxes.php';
require_once HR_PATH . 'includes/render.php';
require_once HR_PATH . 'includes/shortcode.php';
require_once HR_PATH . 'includes/single.php';
require_once HR_PATH . 'includes/photos.php';
require_once HR_PATH . 'includes/header.php';
require_once HR_PATH . 'includes/footer.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once HR_PATH . 'includes/cli.php';
}

register_activation_hook( __FILE__, 'hr_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/**
 * Activation : enregistre les types, crée les catégories par défaut, régénère les permaliens.
 */
function hr_activate() {
	hr_register_content_types();

	$defaults = array(
		'chambres' => __( 'Chambres', 'hotel-rooms' ),
		'suites'   => __( 'Suites', 'hotel-rooms' ),
	);
	foreach ( $defaults as $slug => $name ) {
		if ( ! term_exists( $slug, 'type_hebergement' ) ) {
			wp_insert_term( $name, 'type_hebergement', array( 'slug' => $slug ) );
		}
	}

	flush_rewrite_rules();
}