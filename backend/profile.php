<?php
defined('ABSPATH') or die("No direct access");


/**
* Remove all social profile fields that Yoast adds to user profile Contact Info
*/
add_filter( 'user_contactmethods', function ( $contact_methods ) {
	return array();
});



// Remove color scheme picker from profile page
add_action( 'admin_head-profile.php', function() {
	remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
});


/**
* Hide unwanted fields in the user profile
*/
add_action( 'admin_head-user-edit.php', 'paws_remove_user_profile_fields_with_css' );
add_action( 'admin_head-profile.php',   'paws_remove_user_profile_fields_with_css' );
function paws_remove_user_profile_fields_with_css() {

	$fields_to_hide = [
		'rich-editing',
		'comment-shortcuts',
		'admin-bar-front',
		'language',
		// 'user-login',
		// 'role',
		// 'super-admin',
		'first-name', 
		'last-name', 
		// 'nickname', 
		'display-name', 
		// 'email',
		'description', 
		// 'pass1', 
		// 'pass2', 
		// 'sessions', 
		// 'capabilities',
		'syntax-highlighting',
		'url'
	
	];
	
	//add the CSS
	foreach ($fields_to_hide as $field_name) {
		echo '<style>tr.user-'.$field_name.'-wrap{ display: none; }</style>';
	}

	//fields that don't follow the wrapper naming convention
	echo '<style>#application-passwords-section{ display: none; }</style>';

	//all subheadings
	echo '<style>#your-profile h2{ display: none; }</style>';
}