<?php

add_filter( 'pre_do_shortcode_tag', 'mrp_render_horseclub_latest_post_shortcode', 10, 4 );

function mrp_render_horseclub_latest_post_shortcode( $return, $tag, $attr, $m ) {
	if ( 'horseclub_latest_post' !== $tag ) {
		return $return;
	}

	return mrp_get_horseclub_latest_post_shortcode_output( is_array( $attr ) ? $attr : array() );
}

function mrp_horseclub_build_upload_url_from_relative_path( $relative_path ) {
	if ( ! is_string( $relative_path ) || '' === $relative_path ) {
		return '';
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['baseurl'] ) ) {
		return '';
	}

	return trailingslashit( $uploads['baseurl'] ) . ltrim( str_replace( '\\', '/', $relative_path ), '/' );
}

function mrp_horseclub_get_attachment_relative_file_path( $attachment_id ) {
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

function mrp_horseclub_get_resized_attachment_url( $attachment_id, $width, $height ) {
	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
		$original_relative_path = mrp_horseclub_get_attachment_relative_file_path( $attachment_id );
		$directory              = pathinfo( $original_relative_path, PATHINFO_DIRNAME );
		$directory              = '.' === $directory ? '' : trim( $directory, '/' );

		foreach ( $metadata['sizes'] as $size_data ) {
			if ( empty( $size_data['file'] ) || empty( $size_data['width'] ) || empty( $size_data['height'] ) ) {
				continue;
			}

			if ( (int) $size_data['width'] !== (int) $width || (int) $size_data['height'] !== (int) $height ) {
				continue;
			}

			$relative_path = '' !== $directory ? $directory . '/' . $size_data['file'] : $size_data['file'];
			$url           = mrp_horseclub_build_upload_url_from_relative_path( $relative_path );
			if ( '' !== $url ) {
				return mrp_rewrite_media_url( $url );
			}
		}
	}

	$full_image = mrp_get_attachment_image_src_unfiltered( $attachment_id, 'full' );
	if ( empty( $full_image[0] ) ) {
		$original_relative_path = mrp_horseclub_get_attachment_relative_file_path( $attachment_id );
		$full_url               = mrp_horseclub_build_upload_url_from_relative_path( $original_relative_path );
	} else {
		$full_url = $full_image[0];
	}

	if ( ! is_string( $full_url ) || '' === $full_url ) {
		return '';
	}

	$url_parts = wp_parse_url( $full_url );
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

	if ( ! empty( $url_parts['host'] ) ) {
		$rebuilt_url .= $url_parts['host'];
	}

	if ( ! empty( $url_parts['port'] ) ) {
		$rebuilt_url .= ':' . $url_parts['port'];
	}

	$rebuilt_url .= $resized_path;

	return mrp_rewrite_media_url( $rebuilt_url );
}

function mrp_get_horseclub_latest_post_image_html( $post_id ) {
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( ! $thumbnail_id ) {
		return '';
	}

	$image_url = mrp_horseclub_get_resized_attachment_url( $thumbnail_id, 600, 300 );
	if ( '' === $image_url ) {
		return '';
	}

	$output  = '<div class="latest_post_img">';
	$output .= '<div class="mas_data">';
	$output .= '<div class="mas_data_inner">';
	$output .= '<span class="mas_month">' . esc_html( get_the_date( 'M', $post_id ) ) . '</span>';
	$output .= '<span class="mas_date">' . esc_html( get_the_date( 'j', $post_id ) ) . '</span>';
	$output .= '<span class="mas_year">' . esc_html( get_the_date( 'Y', $post_id ) ) . '</span>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= '<a href="' . esc_url( get_permalink( $post_id ) ) . '"> ';
	$output .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( get_the_title( $post_id ) ) . '" >';
	$output .= '</a>';
	$output .= '</div>';

	return $output;
}

function mrp_get_horseclub_latest_post_excerpt_html() {
	if ( function_exists( 'get_excerpt_up' ) ) {
		return get_excerpt_up( 95 );
	}

	return '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( get_the_content() ), 20 ) ) . '</p>';
}

function mrp_get_horseclub_latest_post_shortcode_output( $atts ) {
	$atts = shortcode_atts(
		array(
			'el_position' => '',
			'postperpage' => '',
			'lplayout'    => '',
			'category'    => '',
		),
		$atts,
		'horseclub_latest_post'
	);

	$lplayoutt = '' !== $atts['lplayout'] ? $atts['lplayout'] : '';
	$query     = new WP_Query(
		array(
			'orderby'        => 'date',
			'posts_per_page' => (int) $atts['postperpage'],
			'category_name'  => $atts['category'],
		)
	);

	$output  = '<div class="up_latest_post ' . esc_attr( $lplayoutt ) . '">';
	$output .= '<ul>';
	$output .= '<li>';

	while ( $query->have_posts() ) {
		$query->the_post();

		if ( empty( $lplayoutt ) ) {
			$output .= '<div class="up_lp">';
		} else {
			$output .= '<div class="up_lpt">';
		}

		$post_id     = get_the_ID();
		$postcontent = get_post_meta( $post_id, '_horseclub_post_type', true );
		$videow      = empty( $lplayoutt ) ? 266 : 362;

		if ( 'video' === $postcontent ) {
			$video   = get_post_meta( $post_id, '_horseclub_post_video', true );
			$output .= '<div class="videofit" style="max-width:' . (int) $videow . 'px;">';
			$output .= $video;
			$output .= '</div>';
		} else {
			$output .= mrp_get_horseclub_latest_post_image_html( $post_id );
		}

		$output .= '<div class="up_latest_post_inner">';
		$output .= '<div class="up_latest_post_title">';
		$output .= '<a href="' . esc_url( get_permalink( $post_id ) ) . '"> ';
		$output .= '<h5 class="posttitle">' . esc_html( get_the_title( $post_id ) ) . '</h5>';
		$output .= '</a>';
		$output .= '<div class="up_latest_post_date"><i class="fa fa-folder-open-o"></i>&nbsp;';

		$categories = get_the_category( $post_id );
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$output .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '" >' . esc_html( $category->cat_name ) . '</a>  ';
			}
		}

		$comments_count = get_comments_number( $post_id );
		switch ( $comments_count ) {
			case 0:
				$comments_count_text = esc_html__( 'No comment', 'horseclub' );
				break;
			case 1:
				$comments_count_text = $comments_count . ' ' . esc_html__( 'Comment', 'horseclub' );
				break;
			default:
				$comments_count_text = $comments_count . ' ' . esc_html__( 'Comments', 'horseclub' );
				break;
		}

		$output .= '<a class="latest_post_comments" href="' . esc_url( get_comments_link( $post_id ) ) . '">' . esc_html( $comments_count_text ) . '</a>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<div class="up_latest_post_ex">' . mrp_get_horseclub_latest_post_excerpt_html() . '</div>';
		$output .= '</div>';
		$output .= '</div>';
	}

	wp_reset_postdata();

	$output .= '</li>';
	$output .= '</ul>';
	$output .= '</div>';

	return mrp_replace_media_urls_in_content( $output );
}
