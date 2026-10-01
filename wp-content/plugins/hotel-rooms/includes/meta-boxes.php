<?php
/**
 * Metabox « Caractéristiques » et galerie (médiathèque WordPress), colonnes d'administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes_chambre', function () {
	add_meta_box( 'hr_details', __( 'Caractéristiques', 'hotel-rooms' ), 'hr_render_details_box', 'chambre', 'normal', 'high' );
	add_meta_box( 'hr_gallery', __( 'Galerie', 'hotel-rooms' ), 'hr_render_gallery_box', 'chambre', 'normal', 'high' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || 'chambre' !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'hr-admin', HR_URL . 'assets/admin.css', array(), HR_VERSION );
	wp_enqueue_script( 'hr-admin', HR_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), HR_VERSION, true );
	wp_localize_script( 'hr-admin', 'hrAdmin', array(
		'title'  => __( 'Images de la galerie', 'hotel-rooms' ),
		'button' => __( 'Ajouter à la galerie', 'hotel-rooms' ),
	) );
} );

function hr_render_details_box( $post ) {
	wp_nonce_field( 'hr_save_room', 'hr_nonce' );
	$room = hr_get_room( $post );
	?>
	<div class="hr-fields">
		<p>
			<label for="hr_code"><?php esc_html_e( 'Code chambre (moteur de réservation)', 'hotel-rooms' ); ?></label>
			<input type="text" id="hr_code" name="hr[code]" value="<?php echo esc_attr( $room['code'] ); ?>" placeholder="DLX">
		</p>
		<p>
			<label for="hr_capacity"><?php esc_html_e( 'Capacité maximale (personnes)', 'hotel-rooms' ); ?></label>
			<input type="number" min="1" max="20" id="hr_capacity" name="hr[capacity]" value="<?php echo esc_attr( $room['capacity'] ); ?>">
		</p>
		<p>
			<label for="hr_surface_min"><?php esc_html_e( 'Surface min. (m²)', 'hotel-rooms' ); ?></label>
			<input type="number" min="0" id="hr_surface_min" name="hr[surface_min]" value="<?php echo esc_attr( $room['surface'][0] ); ?>">
		</p>
		<p>
			<label for="hr_surface_max"><?php esc_html_e( 'Surface max. (m², facultatif)', 'hotel-rooms' ); ?></label>
			<input type="number" min="0" id="hr_surface_max" name="hr[surface_max]" value="<?php echo esc_attr( $room['surface'][1] ?: '' ); ?>">
		</p>
		<p>
			<label for="hr_view"><?php esc_html_e( 'Vue', 'hotel-rooms' ); ?></label>
			<input type="text" id="hr_view" name="hr[view]" value="<?php echo esc_attr( $room['view'] ); ?>" placeholder="<?php esc_attr_e( 'Vue mer', 'hotel-rooms' ); ?>">
		</p>
		<p>
			<label for="hr_bed"><?php esc_html_e( 'Literie', 'hotel-rooms' ); ?></label>
			<input type="text" id="hr_bed" name="hr[bed]" value="<?php echo esc_attr( $room['bed'] ); ?>" placeholder="<?php esc_attr_e( 'Lit King size ou lits jumeaux', 'hotel-rooms' ); ?>">
		</p>
		<p class="hr-wide">
			<label for="hr_details"><?php esc_html_e( 'Description détaillée (affichée sur la fiche, bouton « Découvrir »)', 'hotel-rooms' ); ?></label>
			<textarea id="hr_details" name="hr[details]" rows="9" placeholder="<?php esc_attr_e( 'Un paragraphe par ligne. Le résumé (extrait) reste le texte court des cartes.', 'hotel-rooms' ); ?>"><?php echo esc_textarea( $room['details'] ); ?></textarea>
		</p>
		<p class="hr-wide">
			<label for="hr_amenities"><?php esc_html_e( 'Équipements (un par ligne)', 'hotel-rooms' ); ?></label>
			<textarea id="hr_amenities" name="hr[amenities]" rows="6"><?php echo esc_textarea( implode( "\n", $room['amenities'] ) ); ?></textarea>
		</p>
	</div>
	<p class="description"><?php esc_html_e( 'Le résumé (extrait) s’affiche sur la carte du listing ; le contenu principal sur la fiche détaillée.', 'hotel-rooms' ); ?></p>
	<?php
}

function hr_render_gallery_box( $post ) {
	$ids = hr_sanitize_id_list( get_post_meta( $post->ID, '_hr_gallery', true ) );
	?>
	<input type="hidden" id="hr_gallery" name="hr[gallery]" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<ul class="hr-gallery-list">
		<?php foreach ( $ids as $id ) : ?>
			<li data-id="<?php echo esc_attr( $id ); ?>">
				<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
				<button type="button" class="hr-remove" aria-label="<?php esc_attr_e( 'Retirer', 'hotel-rooms' ); ?>">×</button>
			</li>
		<?php endforeach; ?>
	</ul>
	<p>
		<button type="button" class="button hr-add-images"><?php esc_html_e( 'Ajouter des images', 'hotel-rooms' ); ?></button>
		<span class="description"><?php esc_html_e( 'Glissez pour réordonner. L’image principale est placée en premier automatiquement.', 'hotel-rooms' ); ?></span>
	</p>
	<?php
}

add_action( 'save_post_chambre', function ( $post_id ) {
	if ( ! isset( $_POST['hr_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hr_nonce'] ), 'hr_save_room' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$input = isset( $_POST['hr'] ) ? wp_unslash( (array) $_POST['hr'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- chaque champ est nettoyé par son callback ci-dessous.

	foreach ( hr_meta_schema() as $key => $args ) {
		$field = substr( $key, 4 );
		if ( ! array_key_exists( $field, $input ) ) {
			continue;
		}
		update_post_meta( $post_id, $key, call_user_func( $args['sanitize'], $input[ $field ] ) );
	}
} );

/* Colonnes de la liste d'administration. */
add_filter( 'manage_chambre_posts_columns', function ( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['hr_thumb'] = '';
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['hr_code']     = __( 'Code', 'hotel-rooms' );
			$new['hr_capacity'] = __( 'Pers.', 'hotel-rooms' );
			$new['hr_surface']  = __( 'Surface', 'hotel-rooms' );
		}
	}
	return $new;
} );

add_action( 'manage_chambre_posts_custom_column', function ( $column, $post_id ) {
	$room = hr_get_room( $post_id );
	switch ( $column ) {
		case 'hr_thumb':
			if ( $room['gallery'] ) {
				echo wp_get_attachment_image( $room['gallery'][0], array( 60, 45 ) );
			}
			break;
		case 'hr_code':
			echo esc_html( $room['code'] );
			break;
		case 'hr_capacity':
			echo esc_html( $room['capacity'] );
			break;
		case 'hr_surface':
			echo esc_html( hr_format_surface( $room['surface'], false ) );
			break;
	}
}, 10, 2 );

/* Le listing respecte l'ordre manuel (attribut « Ordre »). */
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() && $query->is_main_query() && 'chambre' === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );