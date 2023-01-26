<?php
defined('ABSPATH') or die("No direct access");


/**
 * Remove unnecessary tags from head
 */
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'index_rel_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

add_action( 'wp_enqueue_scripts', function() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
});

/**
 * Remove emoji CDN hostname from DNS prefetching hints
 */
add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {

	if ( 'dns-prefetch' == $relation_type ) {
		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
		$urls = array_diff( $urls, array( $emoji_svg_url ) );
	}

	return $urls;
}, 10, 2 );

/**
* Disable RSS feeds
*/
add_action('do_feed', 'disable_feeds', 1);
add_action('do_feed_rdf', 'disable_feeds', 1);
add_action('do_feed_rss', 'disable_feeds', 1);
add_action('do_feed_rss2', 'disable_feeds', 1);
add_action('do_feed_atom', 'disable_feeds', 1);
add_action('do_feed_rss2_comments', 'disable_feeds', 1);
add_action('do_feed_atom_comments', 'disable_feeds', 1);
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'feed_links', 2 );
function disable_feeds() {
	wp_redirect( home_url(), 302 );
	exit();
}

/**
 * Remove default favicon
 * https://gist.github.com/webdados/a7702e588070f9a1cfa12dff89b3573c
 */
add_action( 'do_faviconico', function() {

	header( 'Content-Type: image/vnd.microsoft.icon' );
	exit;
} );

/**
 * Make the title tag populate automatically from Yoast
 */
add_theme_support( 'title-tag' );

/**
 * Add logged in body class
 */
add_filter( 'body_class', function ( $classes ) {
    if ( is_user_logged_in() ) {
        $classes[] = 'logged-in';
    }
    return $classes;
});


// todo check what this does exactly
/**
 * Remove admin bar positioning CSS
 */
add_action('admin_bar_init', function() {
	remove_action('wp_head', '_admin_bar_bump_cb');
});

 
/*
* Remove dashicons in frontend for unauthenticated users
*/
add_action( 'wp_enqueue_scripts', 'bs_dequeue_dashicons' );
function bs_dequeue_dashicons() {
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}
}

