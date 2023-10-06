<?php
defined('ABSPATH') or die("No direct access");

//flush permalinks on update

// $current_hash = sha1(json_encode($post_types));
// if (!hash_equals($current_hash, get_option('post_type_array'))) {

// 	update_option('post_type_array', $current_hash);
// 	flush_rewrite_rules();
// }

function create_post_type(
	string $singular_name,
	string $plural_name = '',
	string $slug = '',
	string $icon = 'dashicons-admin-post',
	string $rewrite = '',
	bool $hierarchical = false,
	bool $has_archive = null,
	bool $public = true,
	bool $show_in_rest = true,
	bool $exclude_from_search = null,
	array $supports = [
		'title',
		'thumbnail',
		'editor',
		'author',
		'revisions',
		'excerpt',
	],
): void {

	if (empty($slug)) {
		$slug = str_replace(' ', '-', strtolower($singular_name));
	}

	if(empty($rewrite)) {
		$rewrite = $slug;
	}

	if (empty($plural_name)) {
		$plural_name = $singular_name.'s';
	}

	if ($has_archive === null) {
        $has_archive = $public;
    }

	if ($exclude_from_search === null) {
		$exclude_from_search = !$public;
    }

	register_post_type(
		$slug,
		[
			'labels' => [
				'name' => $plural_name,
				'singular_name' => $singular_name,
				'new_item' => 'New ' . $singular_name,
				'edit_item' => 'Edit ' . $singular_name,
				'view_item' => 'View ' . $singular_name,
				'all_items' => 'All ' . $plural_name,
				'search_items' => 'Search '. $plural_name,
				'not_found' => 'No ' . $plural_name . ' found.',
			],
			'public' => $public,
			'has_archive' => $has_archive,
			'hierarchical' => $hierarchical,
			'menu_icon' => $icon,
			'rewrite' => ['slug' => $rewrite],
			'show_in_rest' => $show_in_rest,
			'exclude_from_search' => $exclude_from_search,
			'supports' => $supports,
		]
	);
}