<?php
defined('ABSPATH') or die("No direct access");

/**
 * Ensure our theme is always active with a fallback to 'paws' if 'theme' does not exist.
 */
add_action('setup_theme', function () {
    $primary_theme = 'theme';
    $fallback_theme = 'paws';

    // Check if the primary theme exists
    if (wp_get_theme($primary_theme)->exists()) {
        switch_theme($primary_theme); // Activate primary theme
    } else {
        switch_theme($fallback_theme); // Fallback to 'paws' theme if 'theme' doesn't exist
    }
});
