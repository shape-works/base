<?php
defined('ABSPATH') or die("No direct access");

function create_taxonomy(
	string $singular_name,
	array $post_types,
	string $plural_name = '',
	string $slug = '',
	string $rewrite = '',
	bool $public = true,
	bool $show_ui = true,
	bool $show_admin_column = true,
	bool $hierarchical = true,
	bool $show_in_rest = true,
	array $default_term = [
		'name' => 'Uncategorised',
		'slug' => 'uncategorised',
		'description' => '',
	]
): void {
	if (empty($slug)) {
		$slug = str_replace(' ', '-', $singular_name);
	}

	if (empty($rewrite)) {
		$rewrite = $slug;
	}

	if (empty($plural_name)) {
		$plural_name = $singular_name . 's';
	}

	register_taxonomy(
		$slug,
		$post_types,
		[
			'labels' => [
				'name' => $plural_name,
				'singular_name' => $singular_name,
			],
			'public' => $public,
			'show_ui' => $show_ui,
			'show_admin_column' => $show_admin_column,
			'rewrite' => ['slug' => $rewrite],
			'hierarchical' => $hierarchical,
			'show_in_rest' => $show_in_rest,
			'default_term' => $default_term,
		]
	);
}
