<?php

add_filter( 'vc_wpb_getimagesize', 'mrp_capture_vc_generated_image_urls', 10, 3 );

function mrp_is_custom_wpb_size( $thumb_size ) {
	global $_wp_additional_image_sizes;

	if ( is_array( $thumb_size ) ) {
		return true;
	}

	if ( ! is_string( $thumb_size ) || '' === $thumb_size ) {
		return false;
	}

	$registered_sizes = array(
		'thumbnail',
		'thumb',
		'medium',
		'large',
		'full',
	);

	if ( in_array( $thumb_size, $registered_sizes, true ) ) {
		return false;
	}

	return empty( $_wp_additional_image_sizes[ $thumb_size ] );
}

function mrp_html_has_empty_img_src( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return false;
	}

	return (bool) preg_match( '/<img\b[^>]*\bsrc=(["\'])\s*\\1/i', $html );
}

function mrp_parse_wpb_size( $size ) {
	if ( is_array( $size ) && isset( $size[0], $size[1] ) ) {
		return array( (int) $size[0], (int) $size[1] );
	}

	if ( ! is_string( $size ) ) {
		return false;
	}

	preg_match_all( '/\d+/', $size, $matches );
	if ( empty( $matches[0] ) ) {
		return false;
	}

	$count = count( $matches[0] );
	if ( $count > 1 ) {
		return array( (int) $matches[0][0], (int) $matches[0][1] );
	}

	if ( 1 === $count ) {
		return array( (int) $matches[0][0], (int) $matches[0][0] );
	}

	return false;
}

function mrp_build_img_html_from_image_data( $attachment_id, $image, $class = '' ) {
	if ( empty( $image[0] ) ) {
		return '';
	}

	$attachment = get_post( $attachment_id );
	$title      = $attachment ? trim( wp_strip_all_tags( $attachment->post_title ) ) : '';
	$alt        = trim( esc_attr( do_shortcode( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) );

	if ( '' === $alt && $attachment ) {
		$alt = trim( wp_strip_all_tags( $attachment->post_excerpt ) );
	}

	if ( '' === $alt ) {
		$alt = $title;
	}

	$attributes = array(
		'src'    => $image[0],
		'width'  => ! empty( $image[1] ) ? $image[1] : '',
		'height' => ! empty( $image[2] ) ? $image[2] : '',
		'alt'    => $alt,
		'title'  => $title,
	);

	if ( '' !== $class ) {
		$attributes['class'] = $class;
	}

	$attributes = vc_stringify_attributes( vc_add_lazy_loading_attribute( $attributes ) );

	return '<img ' . $attributes . ' />';
}

function mrp_get_attachment_metadata_resized_image( $attachment_id, $width, $height ) {
	$image_data = mrp_get_attachment_metadata_resized_image_data( $attachment_id, $width, $height );
	if ( ! $image_data ) {
		return false;
	}

	return array(
		mrp_rewrite_media_url( $image_data['url'] ),
		$image_data['width'],
		$image_data['height'],
	);
}

function mrp_get_attachment_original_image_data( $attachment_id ) {
	$image = mrp_get_attachment_image_src_unfiltered( $attachment_id, 'full' );
	if ( ! empty( $image[0] ) ) {
		return $image;
	}

	$relative_path = mrp_get_attachment_relative_file_path( $attachment_id );
	$url           = mrp_build_upload_url_from_relative_path( $relative_path );
	if ( '' === $url ) {
		return false;
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );

	return array(
		$url,
		! empty( $metadata['width'] ) ? (int) $metadata['width'] : '',
		! empty( $metadata['height'] ) ? (int) $metadata['height'] : '',
	);
}

function mrp_get_attachment_resized_image_data_unfiltered( $attachment_id, $thumb_size ) {
	if ( mrp_is_custom_wpb_size( $thumb_size ) && function_exists( 'wpb_resize' ) ) {
		$dimensions = mrp_parse_wpb_size( $thumb_size );
		if ( $dimensions ) {
			$metadata_image = mrp_get_attachment_metadata_resized_image( $attachment_id, $dimensions[0], $dimensions[1] );
			if ( $metadata_image ) {
				return $metadata_image;
			}

			$resized = mrp_with_rewrite_filters_suspended(
				function () use ( $attachment_id, $dimensions ) {
					return wpb_resize( $attachment_id, null, $dimensions[0], $dimensions[1], true );
				}
			);

			if ( ! empty( $resized['url'] ) ) {
				return array(
					$resized['url'],
					! empty( $resized['width'] ) ? $resized['width'] : '',
					! empty( $resized['height'] ) ? $resized['height'] : '',
				);
			}

			$full_image = mrp_get_attachment_original_image_data( $attachment_id );
			if ( ! empty( $full_image[0] ) ) {
				$derived_url = mrp_build_resized_url_from_original( $full_image[0], $dimensions[0], $dimensions[1] );
				if ( '' !== $derived_url ) {
					return array(
						mrp_rewrite_media_url( $derived_url ),
						$dimensions[0],
						$dimensions[1],
					);
				}
			}
		}
	}

	$image = mrp_get_attachment_image_src_unfiltered( $attachment_id, $thumb_size );
	if ( ! empty( $image[0] ) ) {
		return $image;
	}

	return mrp_get_attachment_image_src_unfiltered( $attachment_id, 'full' );
}

function mrp_get_attachment_fallback_image_html( $attachment_id, $params = array() ) {
	$thumb_size = isset( $params['thumb_size'] ) ? $params['thumb_size'] : 'full';
	$image      = mrp_get_attachment_resized_image_data_unfiltered( $attachment_id, $thumb_size );

	if ( empty( $image[0] ) ) {
		return '';
	}

	return mrp_build_img_html_from_image_data(
		$attachment_id,
		$image,
		isset( $params['class'] ) ? $params['class'] : ''
	);
}

function mrp_get_attachment_image_html_unfiltered( $attachment_id, $size = 'thumbnail', $attr = array() ) {
	if ( mrp_is_custom_wpb_size( $size ) ) {
		$fallback = mrp_get_attachment_fallback_image_html(
			$attachment_id,
			array(
				'thumb_size' => $size,
				'class'      => isset( $attr['class'] ) ? $attr['class'] : '',
			)
		);

		if ( '' !== $fallback ) {
			return $fallback;
		}
	}

	if ( function_exists( 'wpb_getImageBySize' ) ) {
		$image = mrp_wpb_get_image_by_size_unfiltered(
			array(
				'attach_id'  => $attachment_id,
				'thumb_size' => $size,
				'class'      => isset( $attr['class'] ) ? $attr['class'] : '',
			)
		);

		if ( ! empty( $image['thumbnail'] ) ) {
			if ( mrp_html_has_empty_img_src( $image['thumbnail'] ) ) {
				$fallback = mrp_get_attachment_fallback_image_html(
					$attachment_id,
					array(
						'thumb_size' => $size,
						'class'      => isset( $attr['class'] ) ? $attr['class'] : '',
					)
				);

				if ( '' !== $fallback ) {
					return $fallback;
				}
			}

			return $image['thumbnail'];
		}
	}

	return mrp_with_rewrite_filters_suspended(
		function () use ( $attachment_id, $size, $attr ) {
			return wp_get_attachment_image( $attachment_id, $size, false, $attr );
		}
	);
}

function mrp_wpb_get_image_by_size_unfiltered( $params = array() ) {
	return mrp_with_rewrite_filters_suspended(
		function () use ( $params ) {
			return wpb_getImageBySize( $params );
		}
	);
}

function mrp_capture_vc_generated_image_urls( $image_data, $attach_id, $params ) {
	if ( ! empty( $params['thumb_size'] ) && mrp_is_custom_wpb_size( $params['thumb_size'] ) ) {
		$fallback = mrp_get_attachment_fallback_image_html( $attach_id, $params );
		if ( '' !== $fallback ) {
			$image_data['thumbnail'] = $fallback;
		}

		$resized = mrp_get_attachment_resized_image_data_unfiltered( $attach_id, $params['thumb_size'] );
		if ( ! empty( $resized[0] ) ) {
			$image_data['p_img_large'] = mrp_get_attachment_image_src_unfiltered( $attach_id, 'large' );
			if ( empty( $image_data['p_img_large'][0] ) ) {
				$image_data['p_img_large'] = mrp_get_attachment_image_src_unfiltered( $attach_id, 'full' );
			}
		}
	}

	if ( ! empty( $image_data['thumbnail'] ) && mrp_html_has_empty_img_src( $image_data['thumbnail'] ) ) {
		$fallback = mrp_get_attachment_fallback_image_html( $attach_id, $params );
		if ( '' !== $fallback ) {
			$image_data['thumbnail'] = $fallback;
		}
	}

	if ( empty( $image_data['p_img_large'][0] ) ) {
		$large = mrp_get_attachment_image_src_unfiltered( $attach_id, 'large' );
		if ( empty( $large[0] ) ) {
			$large = mrp_get_attachment_image_src_unfiltered( $attach_id, 'full' );
		}
		if ( ! empty( $large[0] ) ) {
			$image_data['p_img_large'] = $large;
		}
	}

	if ( ! empty( $image_data['thumbnail'] ) ) {
		$image_data['thumbnail'] = mrp_rewrite_text_content( $image_data['thumbnail'] );
	}

	if ( ! empty( $image_data['p_img_large'][0] ) ) {
		$image_data['p_img_large'][0] = mrp_rewrite_media_url( $image_data['p_img_large'][0] );
	}

	return $image_data;
}
