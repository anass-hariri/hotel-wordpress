<?php
/**
 * Type de contenu « chambre », taxonomies et métadonnées exposées à l'API REST.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hr_register_content_types' );

function hr_register_content_types() {
	register_post_type( 'chambre', array(
		'labels'        => array(
			'name'               => __( 'Chambres & Suites', 'hotel-rooms' ),
			'singular_name'      => __( 'Hébergement', 'hotel-rooms' ),
			'menu_name'          => __( 'Chambres & Suites', 'hotel-rooms' ),
			'add_new'            => __( 'Ajouter', 'hotel-rooms' ),
			'add_new_item'       => __( 'Ajouter un hébergement', 'hotel-rooms' ),
			'edit_item'          => __( 'Modifier l’hébergement', 'hotel-rooms' ),
			'new_item'           => __( 'Nouvel hébergement', 'hotel-rooms' ),
			'view_item'          => __( 'Voir l’hébergement', 'hotel-rooms' ),
			'search_items'       => __( 'Rechercher', 'hotel-rooms' ),
			'not_found'          => __( 'Aucun hébergement', 'hotel-rooms' ),
			'all_items'          => __( 'Tous les hébergements', 'hotel-rooms' ),
			'featured_image'     => __( 'Image principale', 'hotel-rooms' ),
			'set_featured_image' => __( 'Définir l’image principale', 'hotel-rooms' ),
		),
		'public'        => true,
		'has_archive'   => false,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-building',
		'menu_position' => 22,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields' ),
		'rewrite'       => array( 'slug' => 'hebergements/chambres-et-suites', 'with_front' => false ),
	) );

	register_taxonomy( 'type_hebergement', 'chambre', array(
		'labels'            => array(
			'name'          => __( 'Catégories', 'hotel-rooms' ),
			'singular_name' => __( 'Catégorie', 'hotel-rooms' ),
			'add_new_item'  => __( 'Ajouter une catégorie', 'hotel-rooms' ),
		),
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'hebergements/chambres-et-suites/categorie', 'with_front' => false ),
	) );

	register_taxonomy( 'batiment', 'chambre', array(
		'labels'            => array(
			'name'          => __( 'Bâtiments', 'hotel-rooms' ),
			'singular_name' => __( 'Bâtiment', 'hotel-rooms' ),
			'add_new_item'  => __( 'Ajouter un bâtiment', 'hotel-rooms' ),
		),
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => false,
		'public'            => false,
		'show_ui'           => true,
	) );

	foreach ( hr_meta_schema() as $key => $args ) {
		register_post_meta( 'chambre', $key, array(
			'type'              => $args['type'],
			'single'            => true,
			'default'           => $args['default'],
			'sanitize_callback' => $args['sanitize'],
			'show_in_rest'      => 'array' === $args['type']
				? array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => $args['items'] ) ) )
				: true,
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}

/**
 * Schéma unique des métadonnées, partagé par l'enregistrement, la metabox et le rendu.
 */
function hr_meta_schema() {
	return array(
		'_hr_code'        => array( 'type' => 'string',  'default' => '',      'sanitize' => 'hr_sanitize_code' ),
		'_hr_capacity'    => array( 'type' => 'integer', 'default' => 2,       'sanitize' => 'absint' ),
		'_hr_surface_min' => array( 'type' => 'integer', 'default' => 0,       'sanitize' => 'absint' ),
		'_hr_surface_max' => array( 'type' => 'integer', 'default' => 0,       'sanitize' => 'absint' ),
		'_hr_view'        => array( 'type' => 'string',  'default' => '',      'sanitize' => 'sanitize_text_field' ),
		'_hr_bed'         => array( 'type' => 'string',  'default' => '',      'sanitize' => 'sanitize_text_field' ),
		'_hr_gallery'     => array( 'type' => 'array',   'default' => array(), 'sanitize' => 'hr_sanitize_id_list', 'items' => 'integer' ),
		'_hr_amenities'   => array( 'type' => 'array',   'default' => array(), 'sanitize' => 'hr_sanitize_text_list', 'items' => 'string' ),
		'_hr_details'     => array( 'type' => 'string',  'default' => '',      'sanitize' => 'sanitize_textarea_field' ),
	);
}

function hr_sanitize_code( $value ) {
	return strtoupper( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) );
}

function hr_sanitize_id_list( $value ) {
	if ( is_string( $value ) ) {
		$value = explode( ',', $value );
	}
	return array_values( array_filter( array_map( 'absint', (array) $value ) ) );
}

function hr_sanitize_text_list( $value ) {
	if ( is_string( $value ) ) {
		$value = preg_split( '/\r\n|\r|\n/', $value );
	}
	return array_values( array_filter( array_map( 'sanitize_text_field', (array) $value ), 'strlen' ) );
}

/**
 * Données normalisées d'un hébergement, utilisées par tous les gabarits.
 */
function hr_get_room( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	$data = array();
	foreach ( hr_meta_schema() as $key => $args ) {
		$value = get_post_meta( $post->ID, $key, true );
		$data[ substr( $key, 4 ) ] = ( '' === $value || null === $value ) ? $args['default'] : $value;
	}

	$gallery = (array) $data['gallery'];
	$thumb   = get_post_thumbnail_id( $post );
	if ( $thumb && ! in_array( (int) $thumb, $gallery, true ) ) {
		array_unshift( $gallery, (int) $thumb );
	}

	$types = get_the_terms( $post, 'type_hebergement' );
	$sites = get_the_terms( $post, 'batiment' );

	return array(
		'id'        => $post->ID,
		'title'     => get_the_title( $post ),
		'excerpt'   => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 28 ),
		'url'       => get_permalink( $post ),
		'code'      => $data['code'],
		'capacity'  => (int) $data['capacity'],
		'surface'   => array( (int) $data['surface_min'], (int) $data['surface_max'] ),
		'view'      => $data['view'],
		'bed'       => $data['bed'],
		'amenities' => (array) $data['amenities'],
		'details'   => (string) $data['details'],
		'gallery'   => $gallery,
		'types'     => is_array( $types ) ? wp_list_pluck( $types, 'slug' ) : array(),
		'site'      => is_array( $sites ) ? $sites[0]->name : '',
	);
}