<?php

// Panel ustawien.
add_action( 'admin_menu', 'mrp_register_settings_page' );
add_action( 'admin_init', 'mrp_register_settings' );
add_filter( 'plugin_row_meta', 'mrp_add_settings_link_to_plugin_meta', 10, 2 );

function mrp_add_settings_link_to_plugin_meta( $plugin_meta, $plugin_file ) {
	if ( plugin_basename( MRP_PLUGIN_FILE ) !== $plugin_file ) {
		return $plugin_meta;
	}

	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=' . MRP_SETTINGS_PAGE ) ),
		esc_html__( 'Ustawienia', 'media-redirect' )
	);

	return array_merge( array( $settings_link ), $plugin_meta );
}

function mrp_register_settings_page() {
	add_management_page( 'Media Redirect', 'Media Redirect', 'manage_options', MRP_SETTINGS_PAGE, 'mrp_settings_page' );
}

function mrp_register_settings() {
	$settings = array(
		MRP_OPTION_PRODUCTION_DOMAIN                   => array(
			'sanitize_callback' => 'mrp_sanitize_production_domain',
		),
		MRP_OPTION_CUSTOM_WPCONTENT                    => array(
			'sanitize_callback' => 'mrp_sanitize_custom_wpcontent_path',
		),
		MRP_OPTION_PREFER_LOCAL_UPLOADS                => array(
			'default'           => 1,
			'sanitize_callback' => 'mrp_sanitize_checkbox',
		),
		MRP_OPTION_ENABLE_WPBAKERY_COMPAT              => array(
			'default'           => 0,
			'sanitize_callback' => 'mrp_sanitize_checkbox',
		),
		MRP_OPTION_ENABLE_REVSLIDER_COMPAT             => array(
			'default'           => 0,
			'sanitize_callback' => 'mrp_sanitize_checkbox',
		),
		MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT => array(
			'default'           => 0,
			'sanitize_callback' => 'mrp_sanitize_checkbox',
		),
	);

	foreach ( $settings as $option_name => $args ) {
		register_setting( MRP_SETTINGS_GROUP, $option_name, $args );
	}

	add_settings_section( 'mrp_main_section', '', '__return_empty_string', MRP_SETTINGS_PAGE );

	$fields = array(
		array(
			'id'       => MRP_OPTION_PRODUCTION_DOMAIN,
			'title'    => __( 'Domena produkcyjna', 'media-redirect' ),
			'callback' => 'mrp_render_production_domain_field',
		),
		array(
			'id'       => MRP_OPTION_CUSTOM_WPCONTENT,
			'title'    => __( 'Niestandardowa ścieżka wp-content (opcjonalnie)', 'media-redirect' ),
			'callback' => 'mrp_render_custom_wpcontent_field',
		),
		array(
			'id'       => MRP_OPTION_PREFER_LOCAL_UPLOADS,
			'title'    => __( 'Preferuj lokalne pliki z uploads', 'media-redirect' ),
			'callback' => 'mrp_render_prefer_local_uploads_field',
		),
		array(
			'id'       => MRP_OPTION_ENABLE_WPBAKERY_COMPAT,
			'title'    => __( 'Kompatybilność z wtyczką WPBakery', 'media-redirect' ),
			'callback' => 'mrp_render_wpbakery_compat_field',
		),
		array(
			'id'       => MRP_OPTION_ENABLE_REVSLIDER_COMPAT,
			'title'    => __( 'Kompatybilność z wtyczką Slider Revolution', 'media-redirect' ),
			'callback' => 'mrp_render_revslider_compat_field',
		),
		array(
			'id'       => MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT,
			'title'    => __( 'Kompatybilność z motywem HorseClub', 'media-redirect' ),
			'callback' => 'mrp_render_horseclub_latest_post_compat_field',
		),
	);

	foreach ( $fields as $field ) {
		add_settings_field(
			$field['id'],
			$field['title'],
			$field['callback'],
			MRP_SETTINGS_PAGE,
			'mrp_main_section'
		);
	}
}

function mrp_render_production_domain_field() {
	?>
	<input
		type="text"
		name="<?php echo esc_attr( MRP_OPTION_PRODUCTION_DOMAIN ); ?>"
		value="<?php echo esc_attr( mrp_get_production_domain() ); ?>"
		placeholder="https://domena.pl"
		class="regular-text"
	/>
	<p class="description"><?php esc_html_e( 'Wprowadź pełny adres, włącznie z protokołem http/https.', 'media-redirect' ); ?></p>
	<?php
}

function mrp_render_custom_wpcontent_field() {
	?>
	<input
		type="text"
		name="<?php echo esc_attr( MRP_OPTION_CUSTOM_WPCONTENT ); ?>"
		value="<?php echo esc_attr( mrp_get_custom_wpcontent_path() ); ?>"
		placeholder="/app"
		class="regular-text"
	/>
	<p class="description"><?php esc_html_e( 'Wprowadź tylko, jeśli katalog `wp-content` ma inną nazwę lub ścieżkę.', 'media-redirect' ); ?></p>
	<?php
}

function mrp_render_prefer_local_uploads_field() {
	mrp_render_checkbox_field(
		MRP_OPTION_PREFER_LOCAL_UPLOADS,
		mrp_should_prefer_local_uploads(),
		__( 'Jeśli plik fizycznie istnieje w lokalnym katalogu `uploads`, zostaw lokalny URL zamiast przekierowania.', 'media-redirect' )
	);
}

function mrp_render_wpbakery_compat_field() {
	mrp_render_checkbox_field(
		MRP_OPTION_ENABLE_WPBAKERY_COMPAT,
		mrp_should_enable_wpbakery_compat(),
		__( 'Włącz dodatkowe obejścia dla `vc_single_image` i `vc_gallery` z WPBakery.', 'media-redirect' )
	);
}

function mrp_render_revslider_compat_field() {
	mrp_render_checkbox_field(
		MRP_OPTION_ENABLE_REVSLIDER_COMPAT,
		mrp_should_enable_revslider_compat(),
		__( 'Włącz dodatkowe obejście dla obrazów ładowanych z JSON-a Slider Revolution.', 'media-redirect' )
	);
}

function mrp_render_horseclub_latest_post_compat_field() {
	mrp_render_checkbox_field(
		MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT,
		mrp_should_enable_horseclub_latest_post_compat(),
		__( 'Włącz obejście dla shortcode `horseclub_latest_post`, który renderuje obrazki przez `horseclub_resize()`.', 'media-redirect' )
	);
}

function mrp_render_checkbox_field( $option_name, $checked, $description ) {
	?>
	<input type="hidden" name="<?php echo esc_attr( $option_name ); ?>" value="0" />
	<label>
		<input
			type="checkbox"
			name="<?php echo esc_attr( $option_name ); ?>"
			value="1"
			<?php checked( $checked ); ?>
		/>
		<?php echo esc_html( $description ); ?>
	</label>
	<?php
}

function mrp_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Ustawienia Media Redirect', 'media-redirect' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( MRP_SETTINGS_GROUP ); ?>
			<?php do_settings_sections( MRP_SETTINGS_PAGE ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
