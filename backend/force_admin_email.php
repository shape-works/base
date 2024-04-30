<?php
defined('ABSPATH') or die("No direct access");

/**
 * Force admin email to our value, disable email reminder after login
 */
add_filter('admin_email_check_interval', fn () => false);
add_filter('pre_option_admin_email', fn () => 'dev@shape.works');
add_filter('pre_option_new_admin_email', fn () => 'dev@shape.works');
