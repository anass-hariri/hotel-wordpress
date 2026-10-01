<?php
/**
 * Photos livrées avec le plugin (dossier hotel-rooms/photos/).
 *
 * Elles voyagent avec le code : en hébergeant le site, il suffit de copier le dossier du plugin
 * puis de cliquer sur « Importer les photos » dans Chambres & Suites > Réglages
 * (ou : wp hotel-rooms photos).
 *
 * Répartition :
 * - Sous-dossier au nom de la chambre, tel quel (« Chambre Classique, Deux Fontaines ») ou en slug
 *   (« chambre-classique-deux-fontaines ») : ses photos sont réservées à cette chambre.
 *   Si la chambre n'existe pas encore, elle est créée (catégorie Suites si le nom contient « Suite »).
 * - Photos posées directement dans photos/ : réparties sur les chambres qui n'ont pas de sous-dossier.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hr_photos_dir() {
	return HR_PATH . 'photos';
}

/**
 * Liste les images d'un dossier (non récursif), triées naturellement.
 */
function hr_list_images( $dir ) {
	$files = array();
	if ( ! is_dir( $dir ) ) {
		return $files;
	}
	foreach ( scandir( $dir ) as $name ) {
		if ( preg_match( '/\.(jpe?g|png|webp|avif)$/i', $name ) ) {
			$files[] = $dir . DIRECTORY_SEPARATOR . $name;
		}
	}
	natsort( $files );
	return array_values( $files );
}

/**
 * Sous-dossiers de photos/ (un par chambre), triés naturellement.
 */
function hr_list_room_dirs( $dir ) {
	$dirs = array();
	if ( ! is_dir( $dir ) ) {
		return $dirs;
	}
	foreach ( scandir( $dir ) as $name ) {
		if ( '.' !== $name[0] && is_dir( $dir . DIRECTORY_SEPARATOR . $name ) ) {
			$dirs[] = $name;
		}
	}
	natcasesort( $dirs );
	return array_values( $dirs );
}

/**
 * Retrouve l'hébergement correspondant à un nom de dossier (nom exact ou slug), ou le crée.
 */
function hr_room_for_folder( $folder, $create = true, $order = 0 ) {
	$slug = sanitize_title( $folder );
	foreach ( get_posts( array( 'post_type' => 'chambre', 'post_status' => 'any', 'posts_per_page' => -1 ) ) as $room ) {
		if ( $room->post_name === $slug || sanitize_title( $room->post_title ) === $slug ) {
			return $room;
		}
	}
	if ( ! $create ) {
		return null;
	}

	$id = wp_insert_post( array(
		'post_type'   => 'chambre',
		'post_status' => 'publish',
		'post_title'  => $folder,
		'post_name'   => $slug,
		'menu_order'  => $order,
	), true );
	if ( is_wp_error( $id ) ) {
		return null;
	}
	$type = preg_match( '/\bsuites?\b/i', $folder ) ? 'suites' : 'chambres';
	wp_set_object_terms( $id, $type, 'type_hebergement' );
	return get_post( $id );
}

/**
 * Lit le fichier infos.txt d'un dossier de chambre.
 *
 * Format : une ligne « clé : valeur » par information, puis une ligne « --- »,
 * puis le texte complet de la fiche (paragraphes séparés par une ligne vide).
 * Clés reconnues : extrait, personnes, surface, surface_max, vue, literie, code,
 * ordre, categorie, batiment, equipements (séparés par des virgules).
 */
function hr_read_infos( $dir ) {
	$file = $dir . DIRECTORY_SEPARATOR . 'infos.txt';
	if ( ! is_readable( $file ) ) {
		return null;
	}
	$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$raw = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $raw ); // BOM du Bloc-notes.
	$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	if ( ! mb_check_encoding( $raw, 'UTF-8' ) ) {
		$raw = mb_convert_encoding( $raw, 'UTF-8', 'Windows-1252' );
	}

	$parts = preg_split( '/^\s*-{3,}\s*$/m', $raw, 2 );
	$infos = array();
	foreach ( explode( "\n", $parts[0] ) as $line ) {
		if ( preg_match( '/^\s*([a-zA-Zéè_ ]+?)\s*:\s*(.*)$/u', $line, $m ) ) {
			$key           = str_replace( array( 'é', 'è', ' ' ), array( 'e', 'e', '_' ), strtolower( trim( $m[1] ) ) );
			$infos[ $key ] = trim( $m[2] );
		}
	}
	$infos['_content'] = isset( $parts[1] ) ? trim( $parts[1] ) : '';
	return $infos;
}

/**
 * Applique les informations d'infos.txt à une chambre. Seuls les champs remplis sont modifiés.
 */
function hr_apply_infos( $room_id, $infos ) {
	$post = array( 'ID' => $room_id );
	if ( isset( $infos['extrait'] ) && '' !== $infos['extrait'] ) {
		$post['post_excerpt'] = sanitize_textarea_field( $infos['extrait'] );
	}
	if ( '' !== $infos['_content'] ) {
		// Texte après « --- » : description détaillée de la fiche.
		update_post_meta( $room_id, '_hr_details', sanitize_textarea_field( $infos['_content'] ) );
	}
	if ( isset( $infos['ordre'] ) && is_numeric( $infos['ordre'] ) ) {
		$post['menu_order'] = (int) $infos['ordre'];
	}
	if ( count( $post ) > 1 ) {
		wp_update_post( $post );
	}

	$map = array(
		'code'        => '_hr_code',
		'personnes'   => '_hr_capacity',
		'surface'     => '_hr_surface_min',
		'surface_max' => '_hr_surface_max',
		'vue'         => '_hr_view',
		'literie'     => '_hr_bed',
	);
	$schema = hr_meta_schema();
	foreach ( $map as $key => $meta ) {
		if ( isset( $infos[ $key ] ) && '' !== $infos[ $key ] ) {
			$value = in_array( $key, array( 'personnes', 'surface', 'surface_max' ), true ) ? (int) $infos[ $key ] : $infos[ $key ];
			update_post_meta( $room_id, $meta, call_user_func( $schema[ $meta ]['sanitize'], $value ) );
		}
	}
	// Surface unique : une surface_max vide efface l'ancienne valeur maximale.
	if ( isset( $infos['surface'] ) && '' !== $infos['surface'] && ( ! isset( $infos['surface_max'] ) || '' === $infos['surface_max'] ) ) {
		update_post_meta( $room_id, '_hr_surface_max', 0 );
	}
	if ( ! empty( $infos['equipements'] ) ) {
		update_post_meta( $room_id, '_hr_amenities', hr_sanitize_text_list( array_map( 'trim', explode( ',', $infos['equipements'] ) ) ) );
	}
	if ( ! empty( $infos['categorie'] ) ) {
		wp_set_object_terms( $room_id, sanitize_title( $infos['categorie'] ), 'type_hebergement' );
	}
	if ( ! empty( $infos['batiment'] ) ) {
		$term = term_exists( $infos['batiment'], 'batiment' ) ?: wp_insert_term( $infos['batiment'], 'batiment' );
		if ( ! is_wp_error( $term ) ) {
			wp_set_object_terms( $room_id, (int) ( is_array( $term ) ? $term['term_id'] : $term ), 'batiment' );
		}
	}
}

/**
 * Importe un fichier dans la médiathèque, une seule fois : un second import réutilise le même média.
 */
function hr_import_file( $file ) {
	$key      = md5( basename( dirname( $file ) ) . '/' . basename( $file ) . '|' . filesize( $file ) );
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_hr_source', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => $key,         // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $file ), 'tmp_name' => $tmp ), 0 );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return $id;
	}
	update_post_meta( $id, '_hr_source', $key );
	return (int) $id;
}

/**
 * Importe les photos d'un dossier et les répartit sur les hébergements.
 *
 * @param string        $dir     Dossier source.
 * @param int           $per     Nombre de photos par hébergement (photos communes).
 * @param callable|null $log     Fonction de journalisation facultative.
 * @return array|WP_Error        Résumé : imported, rooms, errors.
 */
function hr_import_photos( $dir, $per = 3, $log = null, $create = true, $prune = false ) {
	$log    = is_callable( $log ) ? $log : '__return_null';
	$errors = array();
	$count  = 0;
	$done   = array(); // ID des chambres déjà servies par leur propre dossier.

	$import_all = function ( $files ) use ( &$errors, &$count ) {
		$ids = array();
		foreach ( $files as $file ) {
			$id = hr_import_file( $file );
			if ( is_wp_error( $id ) ) {
				$errors[] = basename( $file ) . ' : ' . $id->get_error_message();
				continue;
			}
			$ids[] = $id;
			$count++;
		}
		return $ids;
	};

	// 1. Un sous-dossier = une chambre (créée si besoin).
	$folder_rooms = array();
	$created      = 0;
	$described    = 0;
	foreach ( hr_list_room_dirs( $dir ) as $i => $folder ) {
		$existed = (bool) hr_room_for_folder( $folder, false );
		$room    = hr_room_for_folder( $folder, $create, $i );
		if ( ! $room ) {
			$errors[] = sprintf( __( 'Dossier « %s » : aucune chambre de ce nom.', 'hotel-rooms' ), $folder );
			continue;
		}
		if ( ! $existed ) {
			$created++;
			call_user_func( $log, sprintf( 'Chambre créée : %s', $room->post_title ) );
		}
		$folder_rooms[] = $room->ID;

		// Descriptif facultatif : photos/<Nom de la chambre>/infos.txt
		$infos = hr_read_infos( $dir . DIRECTORY_SEPARATOR . $folder );
		if ( $infos ) {
			hr_apply_infos( $room->ID, $infos );
			$described++;
			call_user_func( $log, sprintf( 'Descriptif chargé : %s', $room->post_title ) );
		}

		// Dossier encore vide : la chambre existe, ses photos viendront plus tard.
		$gallery = $import_all( hr_list_images( $dir . DIRECTORY_SEPARATOR . $folder ) );
		if ( ! $gallery ) {
			continue;
		}
		set_post_thumbnail( $room->ID, $gallery[0] );
		update_post_meta( $room->ID, '_hr_gallery', $gallery );
		$done[] = $room->ID;
		call_user_func( $log, sprintf( '%s : %d photo(s)', $room->post_title, count( $gallery ) ) );
	}

	// 2. Photos communes : réparties sur les chambres restantes.
	$shared = $import_all( hr_list_images( $dir ) );
	if ( $shared ) {
		$rooms = get_posts( array(
			'post_type'      => 'chambre',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'post__not_in'   => $done ?: array( 0 ),
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		) );
		$per   = max( 1, (int) $per );
		$total = count( $shared );
		foreach ( $rooms as $i => $room ) {
			$gallery = array();
			for ( $k = 0; $k < $per; $k++ ) {
				$gallery[] = $shared[ ( $i * $per + $k ) % $total ];
			}
			$gallery = array_values( array_unique( $gallery ) );
			set_post_thumbnail( $room->ID, $gallery[0] );
			update_post_meta( $room->ID, '_hr_gallery', $gallery );
			$done[] = $room->ID;
			call_user_func( $log, sprintf( '%s : %d photo(s)', $room->post_title, count( $gallery ) ) );
		}
	}

	// 3. Option : met à la corbeille les chambres qui n'ont pas de dossier (ex. chambres de démo).
	$trashed = 0;
	if ( $prune && $folder_rooms ) {
		foreach ( get_posts( array( 'post_type' => 'chambre', 'post_status' => 'any', 'posts_per_page' => -1, 'post__not_in' => $folder_rooms ) ) as $room ) {
			wp_trash_post( $room->ID );
			$trashed++;
			call_user_func( $log, sprintf( 'Mise à la corbeille : %s', $room->post_title ) );
		}
	}

	if ( ! $count && ! $created && ! $trashed && ! $folder_rooms && ! $described ) {
		return new WP_Error( 'hr_no_photos', __( 'Aucun sous-dossier ni aucune image dans le dossier photos du plugin.', 'hotel-rooms' ) );
	}

	return array(
		'imported' => $count,
		'rooms'    => count( array_unique( $done ) ),
		'created'  => $created,
		'described' => $described,
		'trashed'  => $trashed,
		'errors'   => $errors,
	);
}

/* Bouton « Importer les photos » de la page Réglages. */
add_action( 'admin_post_hr_import_photos', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Accès refusé.', 'hotel-rooms' ) );
	}
	check_admin_referer( 'hr_import_photos' );

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	$per    = isset( $_POST['hr_per'] ) ? absint( $_POST['hr_per'] ) : 3;
	$create = ! empty( $_POST['hr_create'] );
	$prune  = ! empty( $_POST['hr_prune'] );
	$result = hr_import_photos( hr_photos_dir(), $per, null, $create, $prune );

	$args = is_wp_error( $result )
		? array( 'hr_msg' => rawurlencode( $result->get_error_message() ), 'hr_ok' => 0 )
		: array(
			/* translators: 1: chambres créées, 2: photos importées, 3: chambres à la corbeille */
			'hr_msg' => rawurlencode( sprintf( __( '%1$d chambre(s) créée(s), %4$d descriptif(s) chargé(s), %2$d photo(s) importée(s), %3$d chambre(s) mise(s) à la corbeille.', 'hotel-rooms' ), $result['created'], $result['imported'], $result['trashed'], $result['described'] ) . ( $result['errors'] ? ' ' . implode( ' | ', $result['errors'] ) : '' ) ),
			'hr_ok'  => 1,
		);

	wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php?post_type=chambre&page=hr-settings' ) ) );
	exit;
} );

/**
 * Bloc affiché sous les réglages.
 */
function hr_render_photos_box() {
	$files = hr_list_images( hr_photos_dir() );
	$dirs  = hr_list_room_dirs( hr_photos_dir() );
	?>
	<hr>
	<h2><?php esc_html_e( 'Photos du plugin', 'hotel-rooms' ); ?></h2>
	<?php if ( isset( $_GET['hr_msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-<?php echo ! empty( $_GET['hr_ok'] ) ? 'success' : 'error'; // phpcs:ignore WordPress.Security.NonceVerification ?> inline"><p><?php echo esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['hr_msg'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
	<?php endif; ?>
	<p>
		<?php
		printf(
			/* translators: 1: chemin du dossier, 2: nombre de photos, 3: nombre de sous-dossiers */
			esc_html__( 'Dossier : %1$s — %2$d photo(s) commune(s), %3$d sous-dossier(s) de chambre.', 'hotel-rooms' ),
			'<code>' . esc_html( str_replace( ABSPATH, '', hr_photos_dir() ) ) . '</code>',
			count( $files ),
			count( $dirs )
		);
		?>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'hr_import_photos' ); ?>
		<input type="hidden" name="action" value="hr_import_photos">
		<?php if ( $dirs ) : ?>
			<ul style="list-style:disc;margin-left:20px">
				<?php foreach ( $dirs as $folder ) : ?>
					<li>
						<?php echo esc_html( $folder ); ?> —
						<?php
						$room = hr_room_for_folder( $folder, false );
						echo $room
							? esc_html__( 'chambre trouvée', 'hotel-rooms' )
							: '<em>' . esc_html__( 'sera créée', 'hotel-rooms' ) . '</em>';
						?>
						(<?php echo (int) count( hr_list_images( hr_photos_dir() . DIRECTORY_SEPARATOR . $folder ) ); ?> photo(s)<?php echo is_readable( hr_photos_dir() . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'infos.txt' ) ? esc_html__( ', descriptif', 'hotel-rooms' ) : ''; ?>)
					</li>
				<?php endforeach; ?>
			</ul>
			<p><label><input type="checkbox" name="hr_create" value="1" checked> <?php esc_html_e( 'Créer les chambres qui n’existent pas encore (une par dossier, même vide)', 'hotel-rooms' ); ?></label></p>
			<p><label><input type="checkbox" name="hr_prune" value="1"> <?php esc_html_e( 'Mettre à la corbeille les chambres qui n’ont pas de dossier (ex. chambres de démo)', 'hotel-rooms' ); ?></label></p>
		<?php endif; ?>
		<label><?php esc_html_e( 'Photos communes par hébergement :', 'hotel-rooms' ); ?>
			<input type="number" name="hr_per" value="3" min="1" max="10" style="width:60px">
		</label>
		<?php submit_button( __( 'Importer les chambres, descriptifs et photos', 'hotel-rooms' ), 'primary', 'submit', false ); ?>
		<p class="description"><?php esc_html_e( 'Sans risque de doublon : une photo déjà importée est réutilisée. Relancez après avoir ajouté des photos.', 'hotel-rooms' ); ?></p>
	</form>
	<?php
}