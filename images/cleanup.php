<?php
defined('ABSPATH') or die("No direct access");

/*
 * Completely disable image size threshold
 * (to avoid any extra scaled-down image size being generated)
 */
add_filter('big_image_size_threshold', '__return_false');

// This will remove the default image sizes medium and large. 
add_filter('intermediate_image_sizes_advanced', function ($sizes) {
	unset($sizes['medium']);
	unset($sizes['large']);
	return $sizes;
});

//Disable auto image sizes by WP 6.7+
add_filter('wp_img_tag_add_auto_sizes', '__return_false');
