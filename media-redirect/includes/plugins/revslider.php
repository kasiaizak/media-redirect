<?php

add_filter( 'sr_load_slider_json', 'mrp_filter_revslider_json', 10, 2 );

function mrp_rewrite_media_urls_deep( $value ) {
	if ( is_string( $value ) ) {
		return mrp_rewrite_text_content( $value );
	}

	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$value[ $key ] = mrp_rewrite_media_urls_deep( $item );
		}

		return $value;
	}

	if ( is_object( $value ) ) {
		foreach ( get_object_vars( $value ) as $property => $item ) {
			$value->$property = mrp_rewrite_media_urls_deep( $item );
		}
	}

	return $value;
}

function mrp_filter_revslider_json( $data ) {
	if ( ! mrp_should_rewrite_urls() ) {
		return $data;
	}

	return mrp_rewrite_media_urls_deep( $data );
}
