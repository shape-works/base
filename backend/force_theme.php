<?php
defined('ABSPATH') or die("No direct access");

/**
 * Ensure our theme is always active
 */

add_action( 'setup_theme', function(){
    switch_theme('theme');
});