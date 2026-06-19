<?php

function mrp_get_attachment_relative_file_path( $attachment_id ) {
	$relative_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
	if ( is_string( $relative_file ) && '' !== $relative_file ) {
		return ltrim( $relative_file, '/' );
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( ! empty( $metadata['file'] ) && is_string( $metadata['file'] ) ) {
		return ltrim( $metadata['file'], '/' );
	}

	return '';
}

function mrp_build_upload_url_from_relative_path( $relative_path ) {
	if ( ! is_string( $relative_path ) || '' === $relative_path ) {
		return '';
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['baseurl'] ) ) {
		return '';
	}

	return trailingslashit( $uploads['baseurl'] ) . ltrim( str_replace( '\\', '/', $relative_path ), '/' );
}

function mrp_get_attachment_metadata_resized_image_data( $attachment_id, $width, $height ) {
	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
		return false;
	}

	$original_relative_path = mrp_get_attachment_relative_file_path( $attachment_id );
	if ( '' === $original_relative_path ) {
		return false;
	}

	$directory = pathinfo( $original_relative_path, PATHINFO_DIRNAME );
	$directory = '.' === $directory ? '' : trim( $directory, '/' );

	foreach ( $metadata['sizes'] as $size_data ) {
		if ( empty( $size_data['file'] ) || empty( $size_data['width'] ) || empty( $size_data['height'] ) ) {
			continue;
		}

		if ( (int) $size_data['width'] !== (int) $width || (int) $size_data['height'] !== (int) $height ) {
			continue;
		}

		$relative_path = '' !== $directory ? $directory . '/' . $size_data['file'] : $size_data['file'];
		$url           = mrp_build_upload_url_from_relative_path( $relative_path );
		if ( '' === $url ) {
			return false;
		}

		return array(
			'url'    => $url,
			'width'  => (int) $size_data['width'],
			'height' => (int) $size_data['height'],
		);
	}

	return false;
}

function mrp_build_resized_url_from_original( $url, $width, $height ) {
	if ( ! is_string( $url ) || '' === $url || ! $width || ! $height ) {
		return '';
	}

	$url_parts = wp_parse_url( $url );
	if ( empty( $url_parts['path'] ) ) {
		return '';
	}

	$path_info = pathinfo( $url_parts['path'] );
	if ( empty( $path_info['dirname'] ) || empty( $path_info['filename'] ) || empty( $path_info['extension'] ) ) {
		return '';
	}

	$resized_path = $path_info['dirname'] . '/' . $path_info['filename'] . '-' . (int) $width . 'x' . (int) $height . '.' . $path_info['extension'];
	$rebuilt_url  = '';

	if ( ! empty( $url_parts['scheme'] ) ) {
		$rebuilt_url .= $url_parts['scheme'] . '://';
	}

	if ( ! empty( $url_parts['user'] ) ) {
		$rebuilt_url .= $url_parts['user'];
		if ( isset( $url_parts['pass'] ) ) {
			$rebuilt_url .= ':' . $url_parts['pass'];
		}
		$rebuilt_url .= '@';
	}

	if ( ! empty( $url_parts['host'] ) ) {
		$rebuilt_url .= $url_parts['host'];
	}

	if ( ! empty( $url_parts['port'] ) ) {
		$rebuilt_url .= ':' . $url_parts['port'];
	}

	$rebuilt_url .= $resized_path;

	if ( ! empty( $url_parts['query'] ) ) {
		$rebuilt_url .= '?' . $url_parts['query'];
	}

	if ( ! empty( $url_parts['fragment'] ) ) {
		$rebuilt_url .= '#' . $url_parts['fragment'];
	}

	return $rebuilt_url;
}
