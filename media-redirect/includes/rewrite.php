<?php

// Filtry URL-i mediow.
add_filter( 'wp_get_attachment_url', 'mrp_filter_url' );
add_filter( 'wp_get_attachment_image_src', 'mrp_filter_image_src' );
add_filter( 'wp_calculate_image_srcset', 'mrp_filter_srcset' );
add_filter( 'wp_get_attachment_image_attributes', 'mrp_filter_image_src_attribute', 10, 3 );
add_filter( 'the_content', 'mrp_replace_media_urls_in_content', 99 );

add_action( 'template_redirect', 'mrp_start_output_buffer' );
add_action( 'wp_enqueue_scripts', 'mrp_enqueue_frontend_rewrite_script' );

function mrp_with_rewrite_filters_suspended( $callback ) {
	$filters = array(
		array( 'wp_get_attachment_url', 'mrp_filter_url', 10, 1 ),
		array( 'wp_get_attachment_image_src', 'mrp_filter_image_src', 10, 1 ),
		array( 'wp_calculate_image_srcset', 'mrp_filter_srcset', 10, 1 ),
		array( 'wp_get_attachment_image_attributes', 'mrp_filter_image_src_attribute', 10, 3 ),
	);
	$removed = array();

	foreach ( $filters as $filter ) {
		list( $tag, $function, $priority, $accepted_args ) = $filter;

		if ( false !== has_filter( $tag, $function ) ) {
			remove_filter( $tag, $function, $priority );
			$removed[] = $filter;
		}
	}

	try {
		return call_user_func( $callback );
	} finally {
		foreach ( $removed as $filter ) {
			list( $tag, $function, $priority, $accepted_args ) = $filter;
			add_filter( $tag, $function, $priority, $accepted_args );
		}
	}
}

function mrp_backtrace_contains_function( $functions ) {
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) {
		if ( empty( $frame['function'] ) ) {
			continue;
		}

		$function = $frame['function'];
		if ( in_array( $function, $functions, true ) ) {
			return true;
		}

		if ( ! empty( $frame['class'] ) ) {
			$qualified = $frame['class'] . '::' . $function;
			if ( in_array( $qualified, $functions, true ) ) {
				return true;
			}
		}
	}

	return false;
}

function mrp_should_rewrite_urls() {
	$production_domain = mrp_get_production_domain();
	if ( '' === $production_domain ) {
		return false;
	}

	$local_host  = wp_parse_url( home_url(), PHP_URL_HOST );
	$remote_host = wp_parse_url( $production_domain, PHP_URL_HOST );

	return $local_host !== $remote_host;
}

function mrp_is_local_upload_url( $url ) {
	$local_wpcontent_url = trailingslashit( mrp_get_local_wpcontent_url() ) . 'uploads/';

	return strpos( (string) $url, $local_wpcontent_url ) !== false;
}

function mrp_get_upload_relative_path( $url, $base_url ) {
	$url_path  = wp_parse_url( (string) $url, PHP_URL_PATH );
	$base_path = wp_parse_url( (string) $base_url, PHP_URL_PATH );
	if ( ! $url_path || ! $base_path ) {
		return '';
	}

	$normalized_base_path = trailingslashit( rtrim( $base_path, '/' ) );
	if ( strpos( $url_path, $normalized_base_path ) !== 0 ) {
		return '';
	}

	return ltrim( substr( $url_path, strlen( $normalized_base_path ) ), '/' );
}

function mrp_upload_file_exists( $url ) {
	if ( ! mrp_is_local_upload_url( $url ) ) {
		return false;
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
		return false;
	}

	$relative_path = mrp_get_upload_relative_path( $url, $uploads['baseurl'] );
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

function mrp_local_upload_file_exists( $url ) {
	return mrp_should_prefer_local_uploads() && mrp_upload_file_exists( $url );
}

function mrp_is_generated_subsize_url( $url ) {
	$url_path = wp_parse_url( (string) $url, PHP_URL_PATH );
	if ( ! is_string( $url_path ) || '' === $url_path ) {
		return false;
	}

	return (bool) preg_match( '/-\d+x\d+\.(?:avif|gif|jpe?g|png|webp)$/i', $url_path );
}

function mrp_rewrite_media_url( $url ) {
	if ( ! mrp_should_rewrite_urls() || ! mrp_is_local_upload_url( $url ) ) {
		return $url;
	}

	if ( mrp_is_generated_subsize_url( $url ) && mrp_upload_file_exists( $url ) ) {
		return $url;
	}

	if ( mrp_local_upload_file_exists( $url ) ) {
		return $url;
	}

	return str_replace( mrp_get_local_wpcontent_url(), mrp_get_remote_base(), $url );
}

function mrp_get_local_media_url( $url ) {
	$remote_base = mrp_get_remote_base();
	if ( '' === $remote_base || ! is_string( $url ) || '' === $url ) {
		return $url;
	}

	if ( strpos( $url, $remote_base ) !== 0 ) {
		return $url;
	}

	return mrp_get_local_wpcontent_url() . substr( $url, strlen( $remote_base ) );
}

function mrp_rewrite_text_content( $content ) {
	if ( ! mrp_should_rewrite_urls() ) {
		return $content;
	}

	$pattern = '#' . preg_quote( mrp_get_local_wpcontent_url(), '#' ) . '/uploads/[^"\'\s<>)]+#i';

	return preg_replace_callback(
		$pattern,
		function ( $matches ) {
			return mrp_rewrite_media_url( $matches[0] );
		},
		$content
	);
}

function mrp_filter_url( $url ) {
	return mrp_rewrite_media_url( $url );
}

function mrp_filter_image_src( $image ) {
	if ( is_array( $image ) && isset( $image[0] ) ) {
		if ( mrp_should_enable_wpbakery_compat() && mrp_backtrace_contains_function( array( 'wpb_resize' ) ) ) {
			return $image;
		}

		$image[0] = mrp_rewrite_media_url( $image[0] );
	}

	return $image;
}

function mrp_filter_srcset( $sources ) {
	if ( ! mrp_should_rewrite_urls() || ! is_array( $sources ) ) {
		return $sources;
	}

	foreach ( $sources as $key => $source ) {
		if ( ! empty( $source['url'] ) ) {
			$sources[ $key ]['url'] = mrp_rewrite_media_url( $source['url'] );
		}
	}

	return $sources;
}

function mrp_filter_image_src_attribute( $attr, $attachment, $size ) {
	if ( ! mrp_should_rewrite_urls() || empty( $attr['src'] ) ) {
		return $attr;
	}

	$attr['src'] = mrp_rewrite_media_url( $attr['src'] );

	return $attr;
}

function mrp_replace_media_urls_in_content( $content ) {
	return mrp_rewrite_text_content( $content );
}

function mrp_replace_url( $url ) {
	return mrp_rewrite_media_url( $url );
}

function mrp_get_attachment_url_unfiltered( $attachment_id ) {
	return mrp_with_rewrite_filters_suspended(
		function () use ( $attachment_id ) {
			return wp_get_attachment_url( $attachment_id );
		}
	);
}

function mrp_get_attachment_image_src_unfiltered( $attachment_id, $size = 'thumbnail', $icon = false ) {
	return mrp_with_rewrite_filters_suspended(
		function () use ( $attachment_id, $size, $icon ) {
			return wp_get_attachment_image_src( $attachment_id, $size, $icon );
		}
	);
}

function mrp_start_output_buffer() {
	ob_start( 'mrp_rewrite_buffer' );
}

function mrp_rewrite_buffer( $buffer ) {
	return mrp_rewrite_text_content( $buffer );
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
			'localBaseUrl'  => mrp_get_local_wpcontent_url(),
			'remoteBaseUrl' => mrp_get_remote_base(),
		)
	);
}
