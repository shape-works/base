<?php
defined('ABSPATH') or die("No direct access");

/**
 * Remove all core block patterns
 */
remove_theme_support('core-block-patterns');


//stop ability to lock blocks since wp6 update

add_filter('block_editor_settings_all', function ($settings, $context) {
	if ($context->post) {
		$settings['canLockBlocks'] = false;
	}

	return $settings;
}, 10, 2);
