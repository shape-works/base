<?php
defined('ABSPATH') or die("No direct access");

/**
* Remove Help button at top of admin screens
*/
add_filter('admin_head', function (){
	get_current_screen()->remove_help_tabs();
});

/**
* Remove menu bar items
*/
add_action('admin_bar_menu', function ($wp_admin_bar) {
	$wp_admin_bar->remove_node('new-content');
	$wp_admin_bar->remove_node('search');
	$wp_admin_bar->remove_node('customize');
	$wp_admin_bar->remove_node('wp-logo');
	$wp_admin_bar->remove_node('updates');
	$wp_admin_bar->remove_node('view-site');
	$wp_admin_bar->remove_node('appearance');
	$wp_admin_bar->remove_node('dashboard');
	$wp_admin_bar->remove_menu('user-info', 'user-actions');

	$wp_admin_bar->add_node( array(
		'id' => 'my-account',
		'title' => wp_get_current_user()->user_email,
	));
}, 999);


/**
* Disable built-in Posts post type
*/
// Remove Posts from admin menu
add_action( 'admin_menu', function() {
	remove_menu_page( 'edit.php' );
});

// Remove +New post in menu bar
add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'new-post' );
}, 999 );


/**
* Remove version and thank you message from admin footer
*/
add_action( 'admin_menu', function() {
	remove_filter( 'update_footer', 'core_update_footer' ); 
});

add_filter('admin_footer_text', function(){
	return;
});

/**
* Clean up WP login page
*/
add_filter( 'login_head', function() {
	echo '
	<style>
		#login #backtoblog,
		.language-switcher,
		#login .forgetmenot,
		#login h1,
		#login .privacy-policy-page-link {
			display: none;
		}
	</style>';
});

/**
 * Remove 'Templates' full-site editing menu from Gutenberg sidebar
 */
remove_theme_support( 'block-templates' );


// Hide dashboard update notifications for all users
function hide_update_dashboard() {
	remove_action( 'admin_notices', 'update_nag', 3 );
}
	
add_action('admin_menu','hide_update_dashboard');