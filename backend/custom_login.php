<?php
/*
* Change Login WordPress Url from default (wp-admin)
*/

defined('ABSPATH') or die();

add_action('init', function () {
	$is_custom_login_enabled = apply_filters('base_custom_login', true);

	if ($is_custom_login_enabled) {

		global $pagenow;

		if (!(isset($_REQUEST['action']) && $_REQUEST['action'] === 'postpass')) {
			// Proceed with the redirect logic only if 'action' is not 'postpass'
			if ('wp-login.php' == $pagenow && !is_user_logged_in()) {
				wp_redirect('/');
				exit();
			}
		}

		add_action('wp_loaded', 'custom_wp_loaded'); //detect custom login url
		add_filter('site_url', 'custom_site_url', 10, 4);
		add_filter('wp_redirect', 'custom_wp_redirect', 10, 2);
		add_filter('site_option_welcome_email', 'welcome_email'); //update welcome email login url 
		add_filter('logout_url', 'custom_logout_url');
	}
});

function custom_login_slug() {

	$slug = getenv('SITE_LOGIN_SLUG') ? getenv('SITE_LOGIN_SLUG') : 'site-admin';
	return $slug;
}

function custom_login_url($scheme = null) {
	if (get_option('permalink_structure')) {
		return custom_user_trailingslashit(home_url('/', $scheme) . custom_login_slug());
	} else {
		return home_url('/', $scheme) . '?' . custom_login_slug();
	}
}

function prevent_wp_login_access() {
	global $pagenow;

	// Check if the user is trying to access a password-protected post
	if (is_single() && post_password_required()) {
		return; // Bypass the login redirection for password-protected posts
	}

	if ('wp-login.php' == $pagenow && !is_user_logged_in()) {
		wp_redirect('/');
		exit();
	}
}

function custom_use_trailing_slashes() {
	return '/' === substr(get_option('permalink_structure'), -1, 1);
}

function custom_user_trailingslashit($string) {
	return custom_use_trailing_slashes() ? trailingslashit($string) : untrailingslashit($string);
}

function custom_wp_loaded() {

	$req_uri    = $_SERVER['REQUEST_URI'];
	$login_slug = custom_login_slug();
	$login_slug = '/' . $login_slug;

	if (is_admin() && !is_user_logged_in() && !defined('DOING_AJAX') || $req_uri === '/login' || $req_uri === '/login/') {
		//Trying to reach [/wp-admin] OR [/login]
		wp_safe_redirect('/');
		die();
	}

	if (
		$req_uri === $login_slug ||
		substr($req_uri, 0, strlen($login_slug) + 1) === $login_slug . '/' ||
		substr($req_uri, 0, strlen($login_slug) + 1) === $login_slug . '?'
	) {
		//[/login-slug][/login-slug/][/login-slug?*]
		if (!is_user_logged_in()) {
			global $error, $interim_login, $action, $user_login;
			@require_once ABSPATH . 'wp-login.php';
			die();
		} else if (str_contains($req_uri, 'logout')) { //Log out
			@require_once ABSPATH . 'wp-login.php';
			die();
		} else { //Logged in -> Redirect to WP Dashboard
			wp_safe_redirect(admin_url());
			die();
		}
	}
}

function custom_site_url($url, $path, $scheme, $blog_id) {
	return filter_wp_login_php($url, $scheme);
}

function custom_wp_redirect($location, $status) {
	return filter_wp_login_php($location);
}

function filter_wp_login_php($url, $scheme = null) {
	if (isset($_POST['post_password']) || isset($_COOKIE['wp-postpass_' . COOKIEHASH])) {
		return $url; // Do not modify the URL for password-protected posts
	}

	if (strpos($url, 'wp-login.php') !== false && strpos($url, 'action=postpass') === false) {
		if (is_ssl()) {
			$scheme = 'https';
		}

		$args = explode('?', $url);

		if (isset($args[1])) {
			parse_str($args[1], $args);
			$url = add_query_arg($args, custom_login_url($scheme));
		} else {
			$url = custom_login_url($scheme);
		}
	}

	return $url;
}

function welcome_email($value) {
	return $value = str_replace('wp-login.php', custom_login_url(), $value);
}

function custom_logout_url() {
	$logout_url = home_url('/wp/wp-login.php?action=logout');
	$logout_url = add_query_arg('_wpnonce', wp_create_nonce('log-out'), $logout_url);
	return $logout_url;
}
