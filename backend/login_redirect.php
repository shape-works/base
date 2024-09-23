<?php
//Redirect to Page on login
add_filter('login_redirect', function ($redirect_to, $request, $user) {
	// Check if user is trying to access a password-protected post
	if (!empty($_POST['post_password'])) {
		// Allow user to view the password-protected post after entering password
		return $request;
	}

	// Default redirection to admin page
	return admin_url() . 'edit.php?post_type=page';
}, 10, 3);


//Redirect to Page when reaching dashboard
add_action('load-index.php', function () {
	// Check if the current page is password protected
	if (!empty($_POST['post_password'])) {
		return; // Do not redirect if password is being entered
	}

	// Redirect to pages list in the dashboard
	wp_redirect(admin_url('edit.php?post_type=page'));
	exit;
});
