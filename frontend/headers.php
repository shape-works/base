<?php
defined('ABSPATH') or die("No direct access");

/**
 * Add last modified to header
 */

// TODO this resulted in html being cached after we updated site code - find alt or resolve
// add_action('template_redirect', 'paws_add_last_modified_header');
function paws_add_last_modified_header($headers) {
    // Check if we are in a single post of any type (archive pages has not modified date)
    if (is_singular()) {
        $post_id = get_queried_object_id();
        if ($post_id) {
            $post_mtime = get_the_modified_time("D, d M Y H:i:s", $post_id);
            $post_mtime_unix = strtotime($post_mtime);
            $header_last_modified_value = str_replace('+0000', 'GMT', gmdate('r', $post_mtime_unix));
            header("Last-Modified: " . $header_last_modified_value);
        }
    }
}
