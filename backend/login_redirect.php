<?php
add_filter( 'login_redirect', 'custom_login_redirect', 10, 3 );

function custom_login_redirect (){
	return admin_url().'/edit.php?post_type=page';
}