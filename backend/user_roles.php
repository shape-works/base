<?php
defined('ABSPATH') or die("No direct access");

/*
* Rename built-in Administrator role to Developer (slug remains unchanged)
*/
add_action('init', function () {
	global $wp_roles;

	if (!isset($wp_roles)) {
		$wp_roles = new WP_Roles();
	}

	if ($wp_roles->roles['administrator']['name'] = 'Administrator') {
		$wp_roles->roles['administrator']['name'] = 'Developer';
		$wp_roles->role_names['administrator'] = 'Developer';
	}
});

/*
* Custom user roles
*/
add_action('init', function () {

	if (get_option('custom_roles_run_once_01') != 'completed') { // This will only run once to save data into DB

		// add 'Site Admin' role based on Editor with additional capabilities
		add_role('site_admin', 'Site Admin', get_role('editor')->capabilities);

		$role = get_role('site_admin');

		// Extra capabiltiies to add
		$caps = array(
			'list_users',
			'remove_users',
			'edit_users',
			'delete_users',
			'create_users',
			'promote_users',
			'manage_privacy_options',
		);

		// Add all the capabilities by looping through them
		foreach ($caps as $cap) {
			$role->add_cap($cap);
		};

		// remove previously created custom roles
		remove_role('developer');

		// To update the DB after this function was changed, rename the option
		update_option('custom_roles_run_once_01', 'completed');
	}
});

/*
* Change the default role to our new one
*/
add_filter('pre_option_default_role', fn () => 'site_admin');

/*
* Remove 'Administrator' from the list of roles if the current user is not an admin
*/
add_filter('editable_roles', function ($roles) {

	if (isset($roles['administrator']) && !current_user_can('administrator')) {
		unset($roles['administrator']);
	}

	return $roles;
});

/*
* If someone is trying to edit or delete an existing admin and that user isn't an admin, don't allow it
*/
add_filter('map_meta_cap', function ($caps, $cap, $user_id, $args) {

	$check_caps = [
		'edit_user',
		'remove_user',
		'promote_user',
		'delete_user',
		'delete_users'
	];

	if (!in_array($cap, $check_caps) || current_user_can('administrator')) {
		return $caps;
	}

	$other = get_user_by('id', $args[0] ?? false);

	if ($other && $other->has_cap('administrator')) {

		$caps[] = 'do_not_allow';
	}

	return $caps;
}, 10, 4);
