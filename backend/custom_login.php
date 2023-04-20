<?php 
/*
* Change Login WordPress Url from default (wp-admin)
*/

defined('ABSPATH') or die();

add_action('init','prevent_wp_login_access'); //prevent user to reach wp-login.php
add_action( 'wp_loaded', 'custom_wp_loaded' ); //detect custom login url
add_filter( 'site_url', 'custom_site_url', 10, 4 );
add_filter( 'wp_redirect', 'custom_wp_redirect', 10, 2 );
add_filter( 'site_option_welcome_email', 'welcome_email'); //update welcome email login url 

function custom_login_slug() {
	
	$slug = getenv('SITE_LOGIN_SLUG') ? getenv('SITE_LOGIN_SLUG') : 'site-admin';
	return $slug;
}

function custom_login_url( $scheme = null ) {
	if ( get_option( 'permalink_structure' ) ) {
		return custom_user_trailingslashit( home_url( '/', $scheme ) . custom_login_slug() );
	} else {
		return home_url( '/', $scheme ) . '?' . custom_login_slug();
	}
}

function prevent_wp_login_access(){
	global $pagenow;
	if( 'wp-login.php' == $pagenow && !is_user_logged_in()) {
		wp_redirect('/');
		exit();
	}
}

function custom_use_trailing_slashes() {
	return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
}

function custom_user_trailingslashit( $string ) {
	return custom_use_trailing_slashes() ? trailingslashit( $string ) : untrailingslashit( $string );
}
 
function custom_wp_loaded() {

	$req_uri    = $_SERVER['REQUEST_URI'];
	$login_slug = custom_login_slug();
	$login_slug = '/'.$login_slug;
	
	if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {
		//Trying to reach wp-admin
		wp_safe_redirect( '/' );
		die();
	}

	if($req_uri === $login_slug ||
		substr( $req_uri, 0, strlen($login_slug) + 1 ) === $login_slug . '/' ||
		substr( $req_uri, 0, strlen($login_slug) + 1 ) === $login_slug .'?' ){
		//[/login-slug][/login-slug/][/login-slug?*]
		if(!is_user_logged_in()){
			global $error, $interim_login, $action, $user_login;
			@require_once ABSPATH . 'wp-login.php';
			die();
		} else {//Logged in -> Redirect to WP Dashboard
			wp_safe_redirect( admin_url());
			die();
		}
	}
}

function custom_site_url( $url, $path, $scheme, $blog_id ) {
	return filter_wp_login_php( $url, $scheme );
}

function custom_wp_redirect( $location, $status ) {
	return filter_wp_login_php( $location );
}

function filter_wp_login_php( $url, $scheme = null ) {
	if ( strpos( $url, 'wp-login.php' ) !== false ) {
		if ( is_ssl() ) {
			$scheme = 'https';
		}

		$args = explode( '?', $url );

		if ( isset( $args[1] ) ) {
			parse_str( $args[1], $args );
			$url = add_query_arg( $args, custom_login_url( $scheme ) );
		} else {
			$url = custom_login_url( $scheme );
		}
	}

	return $url;
}

function welcome_email( $value ) {
	return $value = str_replace( 'wp-login.php', custom_login_url() , $value );
}
