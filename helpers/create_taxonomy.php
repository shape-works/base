<?php
defined('ABSPATH') or die("No direct access");

function create_taxonomy(
	string $singular_name,
	array $post_types,
	string $plural_name = '',
	string $slug = '',
	array $rewrite = [],
	bool $public = true,
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

	if(empty($rewrite)) {
		$rewrite = [
			'slug' => $name
		];
	}

	if (empty($plural_name)) {
		$plural_name = $singular_name.'s';
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
			'rewrite' => $rewrite,
			'hierarchical' => $hierarchical,
			'show_in_rest' => $show_in_rest,
			'default_term' => $default_term,
		]
	);
}