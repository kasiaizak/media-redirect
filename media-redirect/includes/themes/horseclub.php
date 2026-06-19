<?php

add_filter( 'pre_do_shortcode_tag', 'mrp_render_horseclub_latest_post_shortcode', 10, 4 );

function mrp_render_horseclub_latest_post_shortcode( $return, $tag, $attr, $m ) {
	if ( 'horseclub_latest_post' !== $tag ) {
		return $return;
	}

	return mrp_get_horseclub_latest_post_shortcode_output( is_array( $attr ) ? $attr : array() );
}

function mrp_horseclub_get_resized_attachment_url( $attachment_id, $width, $height ) {
	$metadata_image = mrp_get_attachment_metadata_resized_image_data( $attachment_id, $width, $height );
	if ( $metadata_image ) {
		return mrp_rewrite_media_url( $metadata_image['url'] );
	}

	$full_image = mrp_get_attachment_image_src_unfiltered( $attachment_id, 'full' );
	if ( empty( $full_image[0] ) ) {
		$original_relative_path = mrp_get_attachment_relative_file_path( $attachment_id );
		$full_url               = mrp_build_upload_url_from_relative_path( $original_relative_path );
	} else {
		$full_url = $full_image[0];
	}

	if ( ! is_string( $full_url ) || '' === $full_url ) {
		return '';
	}

	return mrp_rewrite_media_url( mrp_build_resized_url_from_original( $full_url, $width, $height ) );
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
