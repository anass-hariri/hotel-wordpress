<?php
/**
 * Réglages : moteur de réservation, page listing, couleur d'accent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hr_settings_defaults() {
	return array(
		'booking_url'     => '',
		'booking_new_tab' => 1,
		'listing_page'    => 0,
		'accent'          => '#333333',
		'site_bg'         => '#f9f7f4',
		'booking_page'    => 0,
		'booking_email'   => '',
		'header_enabled'  => 1,
		'header_title'    => '',
		'header_subtitle' => '',
	);
}

function hr_get_setting( $key ) {
	$settings = wp_parse_args( (array) get_option( 'hr_settings', array() ), hr_settings_defaults() );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
}

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=chambre',
		__( 'Réglages', 'hotel-rooms' ),
		__( 'Réglages', 'hotel-rooms' ),
		'manage_options',
		'hr-settings',
		'hr_render_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'hr_settings', 'hr_settings', array(
		'type'              => 'array',
		'sanitize_callback' => 'hr_sanitize_settings',
		'default'           => hr_settings_defaults(),
	) );
} );

function hr_sanitize_settings( $input ) {
	$input = (array) $input;
	$url   = isset( $input['booking_url'] ) ? trim( wp_unslash( $input['booking_url'] ) ) : '';
	// esc_url_raw encoderait les accolades du jeton {code} : on le protège.
	$url = str_replace( '{code}', 'HRCODEPLACEHOLDER', $url );
	$url = str_replace( 'HRCODEPLACEHOLDER', '{code}', esc_url_raw( $url ) );

	return array(
		'booking_url'     => $url,
		'booking_new_tab' => empty( $input['booking_new_tab'] ) ? 0 : 1,
		'listing_page'    => isset( $input['listing_page'] ) ? absint( $input['listing_page'] ) : 0,
		'accent'          => sanitize_hex_color( isset( $input['accent'] ) ? $input['accent'] : '' ) ?: '#333333',
		'booking_page'    => isset( $input['booking_page'] ) ? absint( $input['booking_page'] ) : 0,
		'booking_email'   => isset( $input['booking_email'] ) ? sanitize_email( wp_unslash( $input['booking_email'] ) ) : '',
		'header_enabled'  => empty( $input['header_enabled'] ) ? 0 : 1,
		'header_title'    => isset( $input['header_title'] ) ? sanitize_text_field( wp_unslash( $input['header_title'] ) ) : '',
		'header_subtitle' => isset( $input['header_subtitle'] ) ? sanitize_text_field( wp_unslash( $input['header_subtitle'] ) ) : '',
		'site_bg'         => isset( $input['site_bg'] ) ? ( sanitize_hex_color( $input['site_bg'] ) ?: '' ) : '#f9f7f4',
	);
}

function hr_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Chambres & Suites : réglages', 'hotel-rooms' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'hr_settings' ); ?>
			<h2><?php esc_html_e( 'En-tête du site', 'hotel-rooms' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Activer', 'hotel-rooms' ); ?></th>
					<td><label><input type="checkbox" name="hr_settings[header_enabled]" value="1" <?php checked( hr_get_setting( 'header_enabled' ), 1 ); ?>> <?php esc_html_e( 'Remplacer l’en-tête et le pied de page du thème par ceux de l’hôtel sur toutes les pages (contenu du pied de page : includes/footer.php)', 'hotel-rooms' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="hr_header_title"><?php esc_html_e( 'Nom affiché', 'hotel-rooms' ); ?></label></th>
					<td><input type="text" class="regular-text" id="hr_header_title" name="hr_settings[header_title]" value="<?php echo esc_attr( hr_get_setting( 'header_title' ) ); ?>" placeholder="Ti al Lannec">
						<p class="description"><?php esc_html_e( 'Vide = « Ti al Lannec ».', 'hotel-rooms' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="hr_header_subtitle"><?php esc_html_e( 'Sous-titre', 'hotel-rooms' ); ?></label></th>
					<td><input type="text" class="regular-text" id="hr_header_subtitle" name="hr_settings[header_subtitle]" value="<?php echo esc_attr( hr_get_setting( 'header_subtitle' ) ); ?>" placeholder="Trébeurden · Côte de Granit Rose">
						<p class="description"><?php printf( esc_html__( 'Liens du menu : %s, emplacement « Menu de l’en-tête de l’hôtel ». Sans menu, les pages du site sont listées.', 'hotel-rooms' ), '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Apparence > Menus', 'hotel-rooms' ) . '</a>' ); ?></p></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Réservation et affichage', 'hotel-rooms' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="hr_booking_url"><?php esc_html_e( 'URL de réservation', 'hotel-rooms' ); ?></label></th>
					<td>
						<input type="text" class="large-text code" id="hr_booking_url" name="hr_settings[booking_url]"
							value="<?php echo esc_attr( hr_get_setting( 'booking_url' ) ); ?>"
							placeholder="https://reservation.exemple.com/?room={code}">
						<p class="description"><?php esc_html_e( 'Le jeton {code} est remplacé par le code chambre. Sans jeton, le code est ajouté en paramètre ?room=. Laissez vide si vous n’avez pas de moteur de réservation : le bouton utilisera la page ou l’e-mail ci-dessous, ou restera simplement affiché sans lien si les deux sont vides.', 'hotel-rooms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hr_booking_page"><?php esc_html_e( 'Sans moteur : page de demande', 'hotel-rooms' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'hr_settings[booking_page]',
							'id'                => 'hr_booking_page',
							'selected'          => (int) hr_get_setting( 'booking_page' ),
							'show_option_none'  => __( '— Aucune —', 'hotel-rooms' ),
							'option_none_value' => 0,
						) );
						?>
						<p class="description"><?php esc_html_e( 'Par exemple votre page Contact. Le nom de la chambre est ajouté à l’adresse (?chambre=…).', 'hotel-rooms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hr_booking_email"><?php esc_html_e( 'Sans moteur ni page : e-mail', 'hotel-rooms' ); ?></label></th>
					<td>
						<input type="email" class="regular-text" id="hr_booking_email" name="hr_settings[booking_email]" value="<?php echo esc_attr( hr_get_setting( 'booking_email' ) ); ?>" placeholder="reservation@monhotel.com">
						<p class="description"><?php esc_html_e( 'Le bouton ouvre un e-mail « Demande de réservation : nom de la chambre ». Laissez vide pour un bouton sans lien.', 'hotel-rooms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Ouverture', 'hotel-rooms' ); ?></th>
					<td><label><input type="checkbox" name="hr_settings[booking_new_tab]" value="1" <?php checked( hr_get_setting( 'booking_new_tab' ), 1 ); ?>> <?php esc_html_e( 'Ouvrir la réservation dans un nouvel onglet', 'hotel-rooms' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="hr_listing_page"><?php esc_html_e( 'Page listing', 'hotel-rooms' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'hr_settings[listing_page]',
							'id'                => 'hr_listing_page',
							'selected'          => (int) hr_get_setting( 'listing_page' ),
							'show_option_none'  => __( '— Aucune —', 'hotel-rooms' ),
							'option_none_value' => 0,
						) );
						?>
						<p class="description"><?php esc_html_e( 'Page contenant le shortcode [chambres_suites] : sert au lien « Tous les hébergements » sur les fiches.', 'hotel-rooms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="hr_accent"><?php esc_html_e( 'Couleur d’accent', 'hotel-rooms' ); ?></label></th>
					<td><input type="color" id="hr_accent" name="hr_settings[accent]" value="<?php echo esc_attr( hr_get_setting( 'accent' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="hr_site_bg"><?php esc_html_e( 'Couleur de fond du site', 'hotel-rooms' ); ?></label></th>
					<td>
						<input type="color" id="hr_site_bg" name="hr_settings[site_bg]" value="<?php echo esc_attr( hr_get_setting( 'site_bg' ) ?: '#f9f7f4' ); ?>">
						<p class="description"><?php esc_html_e( 'Appliquée à toutes les pages du site (en-tête, contenu, pied de page). Les cartes des chambres restent blanches.', 'hotel-rooms' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php hr_render_photos_box(); ?>
	</div>
	<?php
}