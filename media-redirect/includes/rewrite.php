<?php

// Filtry URL-i mediow.
add_filter( 'wp_get_attachment_url', 'mrp_filter_url' );
add_filter( 'wp_get_attachment_image_src', 'mrp_filter_image_src' );
add_filter( 'wp_calculate_image_srcset', 'mrp_filter_srcset' );
add_filter( 'wp_get_attachment_image_attributes', 'mrp_filter_image_src_attribute', 10, 3 );
add_filter( 'the_content', 'mrp_replace_media_urls_in_content', 99 );

add_action( 'template_redirect', 'mrp_start_output_buffer' );
add_action( 'wp_enqueue_scripts', 'mrp_enqueue_frontend_rewrite_script' );

function mrp_should_rewrite_urls() {
	$production = get_option( 'mrp_production_domain' );
	if ( ! $production ) {
		return false;
	}

	$local  = parse_url( home_url(), PHP_URL_HOST );
	$remote = parse_url( $production, PHP_URL_HOST );

	return $local !== $remote;
}

function mrp_should_prefer_local_uploads() {
	return (bool) get_option( 'mrp_prefer_local_uploads' );
}

function mrp_get_local_wpcontent_url() {
	$custom_wpcontent = trim( (string) get_option( 'mrp_custom_wpcontent' ) );
	if ( '' !== $custom_wpcontent ) {
		return home_url( rtrim( $custom_wpcontent, '/' ) );
	}

	return content_url();
}

function mrp_get_remote_base() {
	$domain              = rtrim( (string) get_option( 'mrp_production_domain' ), '/' );
	$local_wpcontent_url = mrp_get_local_wpcontent_url();

	return $domain . '/' . trim( (string) parse_url( $local_wpcontent_url, PHP_URL_PATH ), '/' );
}

function mrp_is_local_upload_url( $url ) {
	$local_wpcontent_url = mrp_get_local_wpcontent_url();

	return strpos( (string) $url, $local_wpcontent_url . '/uploads/' ) !== false;
}

function mrp_local_upload_file_exists( $url ) {
	if ( ! mrp_should_prefer_local_uploads() || ! mrp_is_local_upload_url( $url ) ) {
		return false;
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
		return false;
	}

	$url_path     = parse_url( (string) $url, PHP_URL_PATH );
	$baseurl_path = parse_url( (string) $uploads['baseurl'], PHP_URL_PATH );
	if ( ! $url_path || ! $baseurl_path ) {
		return false;
	}

	$baseurl_path = rtrim( $baseurl_path, '/' );
	if ( strpos( $url_path, $baseurl_path . '/' ) !== 0 ) {
		return false;
	}

	$relative_path = ltrim( substr( $url_path, strlen( $baseurl_path ) ), '/' );
	if ( '' === $relative_path ) {
		return false;
	}

	$file_path = trailingslashit( $uploads['basedir'] ) . str_replace( '/', DIRECTORY_SEPARATOR, rawurldecode( $relative_path ) );
	$real_base = realpath( $uploads['basedir'] );
	$real_file = realpath( $file_path );
	if ( false === $real_base || false === $real_file ) {
		return false;
	}

	$normalized_base = trailingslashit( wp_normalize_path( $real_base ) );
	$normalized_file = wp_normalize_path( $real_file );

	return strpos( $normalized_file, $normalized_base ) === 0 && is_file( $real_file );
}

function mrp_replace_url( $url ) {
	if ( ! mrp_should_rewrite_urls() || ! mrp_is_local_upload_url( $url ) ) {
		return $url;
	}

	if ( mrp_local_upload_file_exists( $url ) ) {
		return $url;
	}

	return str_replace( mrp_get_local_wpcontent_url(), mrp_get_remote_base(), $url );
}

function mrp_rewrite_upload_urls_in_text( $content ) {
	if ( ! mrp_should_rewrite_urls() ) {
		return $content;
	}

	$pattern = '#' . preg_quote( mrp_get_local_wpcontent_url(), '#' ) . '/uploads/[^"\'\s<>)]+#i';

	return preg_replace_callback(
		$pattern,
		function ( $matches ) {
			return mrp_replace_url( $matches[0] );
		},
		$content
	);
}

function mrp_filter_url( $url ) {
	return mrp_replace_url( $url );
}

function mrp_filter_image_src( $image ) {
	if ( is_array( $image ) && isset( $image[0] ) ) {
		$image[0] = mrp_replace_url( $image[0] );
	}

	return $image;
}

function mrp_filter_srcset( $sources ) {
	if ( ! mrp_should_rewrite_urls() || ! is_array( $sources ) ) {
		return $sources;
	}

	foreach ( $sources as $key => $source ) {
		if ( ! empty( $source['url'] ) ) {
			$sources[ $key ]['url'] = mrp_replace_url( $source['url'] );
		}
	}

	return $sources;
}

function mrp_filter_image_src_attribute( $attr, $attachment, $size ) {
	if ( ! mrp_should_rewrite_urls() || empty( $attr['src'] ) ) {
		return $attr;
	}

	$attr['src'] = mrp_replace_url( $attr['src'] );

	return $attr;
}

function mrp_replace_media_urls_in_content( $content ) {
	return mrp_rewrite_upload_urls_in_text( $content );
}

function mrp_start_output_buffer() {
	ob_start( 'mrp_buffer_start' );
}

function mrp_buffer_start( $buffer ) {
	return mrp_rewrite_upload_urls_in_text( $buffer );
}

function mrp_enqueue_frontend_rewrite_script() {
	if ( ! mrp_should_rewrite_urls() || mrp_should_prefer_local_uploads() ) {
		return;
	}

	wp_enqueue_script(
		'mrp-frontend-rewrite',
		MRP_PLUGIN_URL . 'assets/media-redirect.js',
		array(),
		MRP_VERSION,
		true
	);

	wp_localize_script(
		'mrp-frontend-rewrite',
		'mrpFrontendConfig',
		array(
			'prodDomain' => rtrim( (string) get_option( 'mrp_production_domain' ), '/' ),
		)
	);
}
