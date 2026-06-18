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
	register_setting(
		MRP_SETTINGS_GROUP,
		MRP_OPTION_PRODUCTION_DOMAIN,
		array(
			'sanitize_callback' => 'mrp_sanitize_production_domain',
		)
	);

	register_setting(
		MRP_SETTINGS_GROUP,
		MRP_OPTION_CUSTOM_WPCONTENT,
		array(
			'sanitize_callback' => 'mrp_sanitize_custom_wpcontent_path',
		)
	);

	register_setting(
		MRP_SETTINGS_GROUP,
		MRP_OPTION_PREFER_LOCAL_UPLOADS,
		array(
			'sanitize_callback' => 'mrp_sanitize_checkbox',
		)
	);

	register_setting(
		MRP_SETTINGS_GROUP,
		MRP_OPTION_ENABLE_WPBAKERY_COMPAT,
		array(
			'sanitize_callback' => 'mrp_sanitize_checkbox',
		)
	);

	register_setting(
		MRP_SETTINGS_GROUP,
		MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT,
		array(
			'sanitize_callback' => 'mrp_sanitize_checkbox',
		)
	);

	add_settings_section( 'mrp_main_section', '', '__return_empty_string', MRP_SETTINGS_PAGE );
	add_settings_field(
		MRP_OPTION_PRODUCTION_DOMAIN,
		'Domena produkcyjna',
		'mrp_render_production_domain_field',
		MRP_SETTINGS_PAGE,
		'mrp_main_section'
	);
	add_settings_field(
		MRP_OPTION_CUSTOM_WPCONTENT,
		'Niestandardowa ścieżka wp-content (opcjonalnie)',
		'mrp_render_custom_wpcontent_field',
		MRP_SETTINGS_PAGE,
		'mrp_main_section'
	);
	add_settings_field(
		MRP_OPTION_PREFER_LOCAL_UPLOADS,
		'Preferuj lokalne pliki z uploads',
		'mrp_render_prefer_local_uploads_field',
		MRP_SETTINGS_PAGE,
		'mrp_main_section'
	);
	add_settings_field(
		MRP_OPTION_ENABLE_WPBAKERY_COMPAT,
		'Kompatybilność z wtyczką WPBakery',
		'mrp_render_wpbakery_compat_field',
		MRP_SETTINGS_PAGE,
		'mrp_main_section'
	);
	add_settings_field(
		MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT,
		'Kompatybilność z motywem HorseClub',
		'mrp_render_horseclub_latest_post_compat_field',
		MRP_SETTINGS_PAGE,
		'mrp_main_section'
	);
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
	<p class="description">Wprowadź pełny adres, włącznie z protokołem http/https.</p>
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
	<p class="description">Wprowadź tylko, jeśli katalog `wp-content` ma inną nazwę lub ścieżkę.</p>
	<?php
}

function mrp_render_prefer_local_uploads_field() {
	?>
	<input type="hidden" name="<?php echo esc_attr( MRP_OPTION_PREFER_LOCAL_UPLOADS ); ?>" value="0" />
	<label>
		<input
			type="checkbox"
			name="<?php echo esc_attr( MRP_OPTION_PREFER_LOCAL_UPLOADS ); ?>"
			value="1"
			<?php checked( mrp_should_prefer_local_uploads() ); ?>
		/>
		Jeśli plik fizycznie istnieje w lokalnym katalogu `uploads`, zostaw lokalny URL zamiast przekierowania.
	</label>
	<?php
}

function mrp_render_wpbakery_compat_field() {
	?>
	<input type="hidden" name="<?php echo esc_attr( MRP_OPTION_ENABLE_WPBAKERY_COMPAT ); ?>" value="0" />
	<label>
		<input
			type="checkbox"
			name="<?php echo esc_attr( MRP_OPTION_ENABLE_WPBAKERY_COMPAT ); ?>"
			value="1"
			<?php checked( mrp_should_enable_wpbakery_compat() ); ?>
		/>
		Włącz dodatkowe obejścia dla `vc_single_image` i `vc_gallery` z WPBakery.
	</label>
	<?php
}

function mrp_render_horseclub_latest_post_compat_field() {
	?>
	<input type="hidden" name="<?php echo esc_attr( MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT ); ?>" value="0" />
	<label>
		<input
			type="checkbox"
			name="<?php echo esc_attr( MRP_OPTION_ENABLE_HORSECLUB_LATEST_POST_COMPAT ); ?>"
			value="1"
			<?php checked( mrp_should_enable_horseclub_latest_post_compat() ); ?>
		/>
		Włącz obejście dla shortcode `horseclub_latest_post`, który renderuje obrazki przez `horseclub_resize()`.
	</label>
	<?php
}

function mrp_settings_page() {
	?>
	<div class="wrap">
		<h1>Ustawienia Media Redirect</h1>
		<form method="post" action="options.php">
			<?php settings_fields( MRP_SETTINGS_GROUP ); ?>
			<?php do_settings_sections( MRP_SETTINGS_PAGE ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
