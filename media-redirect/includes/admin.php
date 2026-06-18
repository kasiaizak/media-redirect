<?php

// Panel ustawien.
add_action( 'admin_menu', 'mrp_register_settings_page' );
add_action( 'admin_init', 'mrp_register_settings' );

function mrp_register_settings_page() {
	add_options_page( 'Media Redirect', 'Media Redirect', 'manage_options', MRP_SETTINGS_PAGE, 'mrp_settings_page' );
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

	add_settings_section( 'mrp_main_section', '', '__return_empty_string', MRP_SETTINGS_PAGE );
	add_settings_field(
		MRP_OPTION_PRODUCTION_DOMAIN,
		'Domena produkcyjna (z https://)',
		'mrp_render_production_domain_field',
		MRP_SETTINGS_PAGE,
		'mrp_main_section'
	);
	add_settings_field(
		MRP_OPTION_CUSTOM_WPCONTENT,
		'Niestandardowa sciezka wp-content (opcjonalnie)',
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
	<p class="description">Wprowadz tylko jesli katalog `wp-content` ma inna nazwe lub sciezke.</p>
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
		Jesli plik fizycznie istnieje w lokalnym katalogu `uploads`, zostaw lokalny URL zamiast przekierowania.
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
