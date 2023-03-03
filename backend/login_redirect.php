<?php
//Redirect to Page on login
add_filter( 'login_redirect', function(){
	return admin_url().'/edit.php?post_type=page';
}, 10, 3 );

//Redirect to Page when reaching dashboard
add_action('load-index.php',function(){
	wp_redirect(admin_url('edit.php?post_type=page'));
});