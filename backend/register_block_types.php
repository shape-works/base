<?php
defined('ABSPATH') or die("No direct access");

function get_block_paths(): array {
	return array_merge(
		glob(get_template_directory() . '/blocks/*/block.json'),
		glob(get_template_directory() . '/blocks/*/*/block.json'),
		glob(get_template_directory() . '/blocks-gutenberg/*/block.json'),
		glob(get_template_directory() . '/blocks-gutenberg/*/*/block.json')
	);
}


/**
 * Register blocks
 */
add_action('init', function () {

	global $allowed_block_types;
	$allowed_block_types = [];

	foreach (get_block_paths() as $block_json_path) {

		register_block_type($block_json_path);

		$block_json = json_decode(
			file_get_contents($block_json_path),
			true
		);

		if (isset($block_json['postTypes'])) { //Specific Post Type
			foreach ($block_json['postTypes'] as $post_type) {
				$allowed_block_types[$post_type][] = $block_json['name'];
			}
		} else { //Default: Any Post Type
			$allowed_block_types['all'][] = $block_json['name'];
		}


		$block_folder_path = str_replace('/block.json', '', $block_json_path);

		if (file_exists($block_folder_path . '/init.php')) {
			include_once $block_folder_path . '/init.php';
		}
	}
}, 5);

add_filter('allowed_block_types_all', function ($allowed_blocks, $editor_context) {

	if (empty($editor_context->post)) {
		return $allowed_blocks;
	}

	global $allowed_block_types;
	$allowed_blocks = $allowed_block_types[$editor_context->post->post_type] ?? [];

	if (isset($allowed_block_types['all'])) {
		$allowed_blocks = array_merge(
			$allowed_blocks,
			$allowed_block_types['all']
		);
	}
	return apply_filters('base_blocks_allowed_everywhere', $allowed_blocks);
}, 10, 2);

/**
 * Load ACF field groups for blocks
 */
add_filter('acf/settings/load_json', function ($acf_json_paths) {

	foreach (get_block_paths() as $block_path) {
		array_push(
			$acf_json_paths,
			str_replace('/block.json', '', $block_path)
		);
	}

	return $acf_json_paths;
});
