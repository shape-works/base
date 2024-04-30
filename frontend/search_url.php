<?php
defined('ABSPATH') or die("No direct access");

//TODO make sure there's no better way to do this ie hook into seach permalink and not just redirect
/**
 * Prettify search URL
 */
add_action('template_redirect', function () {

    if (is_search() && !empty($_GET['s'])) { // if there's a search query param

        if (count($_GET) == 1) { // if there are no further params (filters)

            wp_redirect(home_url("/search/") . urlencode(get_query_var('s')));
        } else {

            $query_string_in_full = $_SERVER['QUERY_STRING'];
            $first_and_position = strpos($query_string_in_full, '&');
            $stuff_after_first_and = substr($query_string_in_full, ($first_and_position + 1));

            wp_redirect(home_url("/search/") . urlencode(get_query_var('s')) . "?" . $stuff_after_first_and);
        }

        exit();
    }
});
