<?php

function mrp_get_production_domain() {
	return rtrim( (string) get_option( MRP_OPTION_PRODUCTION_DOMAIN ), '/' );
}

function mrp_get_custom_wpcontent_path() {
	return trim( (string) get_option( MRP_OPTION_CUSTOM_WPCONTENT ) );
}

function mrp_should_prefer_local_uploads() {
	return (bool) get_option( MRP_OPTION_PREFER_LOCAL_UPLOADS );
}

function mrp_should_enable_wpbakery_compat() {
	$enabled = get_option( MRP_OPTION_ENABLE_WPBAKERY_COMPAT, 'legacy_enabled' );

	if ( 'legacy_enabled' === $enabled ) {
		return true;
	}

	return (bool) $enabled;
}

function mrp_get_local_wpcontent_url() {
	$custom_wpcontent = mrp_get_custom_wpcontent_path();
	if ( '' !== $custom_wpcontent ) {
		return home_url( rtrim( $custom_wpcontent, '/' ) );
	}

	return content_url();
}

function mrp_get_remote_base() {
	$production_domain = mrp_get_production_domain();
	if ( '' === $production_domain ) {
		return '';
	}

	$local_wpcontent_path = trim( (string) wp_parse_url( mrp_get_local_wpcontent_url(), PHP_URL_PATH ), '/' );
	if ( '' === $local_wpcontent_path ) {
		return $production_domain;
	}

	return $production_domain . '/' . $local_wpcontent_path;
}

function mrp_sanitize_production_domain( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	return rtrim( esc_url_raw( $value ), '/' );
}

function mrp_sanitize_custom_wpcontent_path( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	$value = '/' . ltrim( $value, '/' );

	return untrailingslashit( sanitize_text_field( $value ) );
}

function mrp_sanitize_checkbox( $value ) {
	return empty( $value ) ? 0 : 1;
}
