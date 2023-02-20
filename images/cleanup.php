<?php
defined('ABSPATH') or die("No direct access");

/*
 * Completely disable image size threshold
 * (to avoid any extra scaled-down image size being generated)
 */
add_filter( 'big_image_size_threshold', '__return_false' );

// This will remove the default image sizes medium and large. 
add_filter( 'intermediate_image_sizes_advanced', function ( $sizes ) {
	unset( $sizes['medium']);
	unset( $sizes['large']);
	return $sizes;
});