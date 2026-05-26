<?php

// Panel ustawien.
add_action( 'admin_menu', 'mrp_register_settings_page' );
add_action( 'admin_init', 'mrp_register_settings' );

function mrp_register_settings_page() {
	add_options_page( 'Media Redirect', 'Media Redirect', 'manage_options', 'media-redirect', 'mrp_settings_page' );
}

function mrp_register_settings() {
	register_setting( 'mrp_settings_group', 'mrp_production_domain' );
	register_setting( 'mrp_settings_group', 'mrp_custom_wpcontent' );
	register_setting( 'mrp_settings_group', 'mrp_prefer_local_uploads' );
}

function mrp_settings_page() {
	?>
	<div class="wrap">
		<h1>Ustawienia Media Redirect</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mrp_settings_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">Domena produkcyjna (z https://)</th>
					<td>
						<input type="text" name="mrp_production_domain"
								value="<?php echo esc_attr( get_option( 'mrp_production_domain' ) ); ?>"
								placeholder="https://domena.pl"
								class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">Niestandardowa sciezka wp-content (opcjonalnie)</th>
					<td>
						<input type="text" name="mrp_custom_wpcontent"
								value="<?php echo esc_attr( get_option( 'mrp_custom_wpcontent' ) ); ?>"
								placeholder="/app"
								class="regular-text" />
						<p class="description">Wprowadz tylko jesli Twoj katalog `wp-content` ma inna nazwe lub sciezke.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Preferuj lokalne pliki z uploads</th>
					<td>
						<input type="hidden" name="mrp_prefer_local_uploads" value="0" />
						<label>
							<input type="checkbox" name="mrp_prefer_local_uploads" value="1" <?php checked( get_option( 'mrp_prefer_local_uploads' ), 1 ); ?> />
							Jesli plik fizycznie istnieje w lokalnym katalogu `uploads`, zostaw lokalny URL zamiast przekierowania.
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
