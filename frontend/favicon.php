<?php
defined('ABSPATH') or die("No direct access");

// set frontend favicons
add_action('wp_head',function(){
	$env = getenv('WP_ENV');
	$favicon_url = get_template_directory_uri().'/assets/images/favicons/'; 
	
	if($env){//If WP_ENV variable exists
		$env == 'development' ? $favicon_url .= 'favicon-dev.png' : '';
		$env == 'staging' ? $favicon_url .= 'favicon-staging.png' : '';
		$env == 'production' ? $favicon_url .= 'favicon.png' : '';
	}
	else {//Fallback
		$favicon_url = get_template_directory_uri().'/assets/images/favicons/favicon.png'; 
	}

	echo '<link rel="shortcut icon" type="image/png" href="'.$favicon_url.'" />';
});

// set backend favicons
add_action( 'admin_head', function() {
	$env = getenv('WP_ENV');
	$favicon_url = get_template_directory_uri().'/assets/images/favicons/'; 
	
	if($env){
		$env == 'development' ? $favicon_url .= 'favicon-dev.png' : '';
		$env == 'staging' ? $favicon_url .= 'favicon-staging.png' : '';
		$env == 'production' ? $favicon_url .= 'favicon-production.png' : '';
	}
	else {
		$favicon_url = get_template_directory_uri().'/assets/images/favicons/favicon.png'; 
	}

	echo '<link rel="shortcut icon" type="image/png" href="'.$favicon_url.'" />';
});