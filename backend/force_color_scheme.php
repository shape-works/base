<?php
defined('ABSPATH') or die("No direct access");

/**
 * Force modern color scheme
 */

// Force modern color scheme for all users
add_filter('get_user_option_admin_color', fn ($color_scheme) => 'modern', 5);

// Remove color scheme picker from profile page
remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');

// Add modern color scheme css for admin bar on frontend
add_action('wp_enqueue_scripts', function () {
	if (is_admin_bar_showing()) {
?>
		<style>
			#wpadminbar {
				color: #fff;
				background: #1e1e1e;
			}

			#wpadminbar:not(.mobile) .ab-top-menu>li>.ab-item:focus,
			#wpadminbar.nojq .quicklinks .ab-top-menu>li>.ab-item:focus,
			#wpadminbar:not(.mobile) .ab-top-menu>li:hover>.ab-item,
			#wpadminbar .ab-top-menu>li.hover>.ab-item {
				color: #33f078 !important;
				background: #0c0c0c;
			}

			#wpadminbar:not(.mobile) li:hover .ab-icon:before,
			#wpadminbar:not(.mobile) li:hover .ab-item:before,
			#wpadminbar:not(.mobile) li:hover .ab-item:after,
			#wpadminbar:not(.mobile) li:hover #adminbarsearch:before,
			#wpadminbar .quicklinks .menupop ul li a:hover,
			#wpadminbar .quicklinks .menupop ul li a:focus,
			#wpadminbar .quicklinks .menupop ul li a:hover strong,
			#wpadminbar .quicklinks .menupop ul li a:focus strong,
			#wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover>a,
			#wpadminbar .quicklinks .menupop.hover ul li a:hover,
			#wpadminbar .quicklinks .menupop.hover ul li a:focus,
			#wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:hover,
			#wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:focus,
			#wpadminbar.nojs .quicklinks .menupop:hover ul li a:hover,
			#wpadminbar.nojs .quicklinks .menupop:hover ul li a:focus,
			#wpadminbar li:hover .ab-icon:before,
			#wpadminbar li:hover .ab-item:before,
			#wpadminbar li a:focus .ab-icon:before,
			#wpadminbar li .ab-item:focus:before,
			#wpadminbar li .ab-item:focus .ab-icon:before,
			#wpadminbar li.hover .ab-icon:before,
			#wpadminbar li.hover .ab-item:before,
			#wpadminbar li:hover #adminbarsearch:before,
			#wpadminbar li #adminbarsearch.adminbar-focused:before,
			#wpadminbar:not(.mobile)>#wp-toolbar li:hover span.ab-label,
			#wpadminbar>#wp-toolbar li.hover span.ab-label,
			#wpadminbar:not(.mobile)>#wp-toolbar a:focus span.ab-label {
				color: #33f078 !important;
			}
		</style>
<?php
	}
});
