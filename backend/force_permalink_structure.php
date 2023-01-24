<?php
defined('ABSPATH') or die("No direct access");

/**
* Force 'post' redirect rules to have pretty permalinks enabled.
*/
add_action( 'init', function () {

	global $wp_rewrite; 
	$wp_rewrite->set_permalink_structure('/%postname%/'); 
	update_option( "rewrite_rules", FALSE );
});


//TODO make sure permalinks are flushed at least once post install