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
	bool $has_archive = true,
	bool $public = true,
	bool $show_in_rest = true,
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

	register_post_type(
		$slug,
		[
			'labels' => [
				'name' => $plural_name,
				'singular_name' => $singular_name,
			],
			'public' => $public,
			'has_archive' => $has_archive,
			'hierarchical' => $hierarchical,
			'menu_icon' => $icon,
			'rewrite' => ['slug' => $rewrite],
			'show_in_rest' => $show_in_rest,
			'supports' => $supports,
		]
	);
}