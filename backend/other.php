<?php
defined('ABSPATH') or die("No direct access");

/**
 * ???
 */
add_action( 'rest_api_init', function() {

	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

	add_filter( 'rest_pre_serve_request', function( $value ) {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Expose-Headers: Link', false );

		return $value;
	});

}, 15 );

/**
 * This code helps with ACF fields returning null in the post preview
 * https://gist.github.com/ChrisLTD/892eccf385752dadaf5f
 * https://support.advancedcustomfields.com/forums/topic/preview-solution/page/3/
 */
add_filter( 'wp_insert_post_data', function ( $data ) {
	if ( isset( $_GET['meta-box-loader'] ) ) {
		unset( $data["post_modified"] );
		unset( $data["post_modified_gmt"] );
	}

	return $data;
} );


/**
* Enable featured images in posts
*/
add_theme_support('post-thumbnails');

/**
* Improve font rendering throughout wp-admin
*/
add_action('admin_head', function() {
	echo '<style>
		body {
			text-rendering: geometricPrecision;
		} 
	</style>';
});

add_action( 'admin_menu', function() {
	remove_menu_page( 'themes.php' );
	remove_menu_page( 'index.php' );

	if( !in_array('administrator', wp_get_current_user()->roles) ){
		remove_menu_page( 'options-general.php' );
	}
}, 999 );


// Add a custom link Simple History under the Tools menu
function custom_tools_menu_link() {
    if (is_plugin_active('simple-history/index.php')) {
        add_submenu_page(
            'tools.php',                  // parent slug
            'Simple History log',         // page title
            'Simple History log',                // menu title
            'manage_options',             // capability
            '/index.php?page=simple_history_page',  // custom link URL
            '',                           // callback function (empty)
            null                          // icon URL (null)
        );
    }
}
add_action('admin_menu', 'custom_tools_menu_link');
