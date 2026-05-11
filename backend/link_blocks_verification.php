<?php
defined('ABSPATH') or die("No direct access");

/*
 * Editor helper for checking blocks with links against WordPress posts.
 * 
 * Usage (to add in specific theme if needed):
	add_filter('sw_add_extra_links_to_scan_attributes', function ($attributes) {
		$attributes['theme/some-block'] = [
			'customLink1',
			'customLinkAttr2',
		];

		$attributes['theme/another-block'] = [
			'link3',
		];
    return $attributes;
	});
* 
*/
function sw_get_extra_link_attributes(): array {
	$attributes = apply_filters(
		'sw_add_extra_links_to_scan_attributes',
		[]
	);

	if (! is_array($attributes)) {
		return [];
	}

	return $attributes;
}

function sw_get_link_attributes_for_block(string $block_name, array $attributes): array {
	$link_attributes = [];

	/**
	 * Automatically scan:
	 * Any theme/* block containing a "link" attribute.
	 */
	if (
		str_starts_with($block_name, 'theme/') &&
		isset($attributes['link']) &&
		is_array($attributes['link'])
	) {
		$link_attributes[] = [
			'attribute' => 'link',
			'link'      => $attributes['link'],
		];
	}

	/**
	 * Additional manually configured attributes.
	 */
	$extra_link_attributes = sw_get_extra_link_attributes();

	if (! empty($extra_link_attributes[$block_name])) {
		$configured_attributes = $extra_link_attributes[$block_name];

		if (is_string($configured_attributes)) {
			$configured_attributes = [$configured_attributes];
		}

		if (is_array($configured_attributes)) {
			foreach ($configured_attributes as $attribute_name) {
				if (
					isset($attributes[$attribute_name]) &&
					is_array($attributes[$attribute_name])
				) {
					$link_attributes[] = [
						'attribute' => $attribute_name,
						'link'      => $attributes[$attribute_name],
					];
				}
			}
		}
	}

	return $link_attributes;
}

function sw_get_linked_post_validation_result_for_post_id(int $post_id): array {
	if (! $post_id) {
		return [
			'id'      => null,
			'status'  => 'no-id',
			'message' => 'No post ID in link attribute.',
		];
	}

	$post = get_post($post_id);

	if (! $post) {
		return [
			'id'      => $post_id,
			'status'  => 'missing',
			'message' => "This post doesn't exist.",
		];
	}

	if ($post->post_status === 'publish') {
		return [
			'id'      => $post_id,
			'status'  => 'publish',
			'message' => '',
		];
	}

	if ($post->post_status === 'trash') {
		return [
			'id'      => $post_id,
			'status'  => 'trash',
			'message' => 'Post linked is in the trash.',
		];
	}

	if ($post->post_status === 'draft') {
		return [
			'id'      => $post_id,
			'status'  => 'draft',
			'message' => 'Post linked is a draft.',
		];
	}

	if ($post->post_status === 'private') {
		return [
			'id'      => $post_id,
			'status'  => 'private',
			'message' => 'Post linked is private.',
		];
	}

	if ($post->post_status === 'pending') {
		return [
			'id'      => $post_id,
			'status'  => 'pending',
			'message' => 'Post linked is pending review.',
		];
	}

	if ($post->post_status === 'future') {
		return [
			'id'      => $post_id,
			'status'  => 'future',
			'message' => 'Post linked is scheduled.',
		];
	}

	if ($post->post_status === 'auto-draft') {
		return [
			'id'      => $post_id,
			'status'  => 'auto-draft',
			'message' => 'Post linked is an auto draft.',
		];
	}

	$status_object = get_post_status_object($post->post_status);
	$status_label  = $status_object
		? $status_object->label
		: $post->post_status;

	return [
		'id'      => $post_id,
		'status'  => $post->post_status,
		'message' => sprintf(
			'This post is not published: %s.',
			$status_label
		),
	];
}

function sw_get_link_validation_results(string $content): array {
	$results = [];

	if (! function_exists('parse_blocks')) {
		return [
			[
				'blockNumber' => 0,
				'id'          => null,
				'status'      => 'unavailable',
				'message'     => 'parse_blocks is not available.',
			],
		];
	}

	$blocks       = parse_blocks($content);
	$block_number = 0;

	$walk_blocks = function (array $blocks) use (
		&$walk_blocks,
		&$results,
		&$block_number
	): void {

		foreach ($blocks as $block) {
			$block_name = $block['blockName'] ?? '';

			if ($block_name) {
				$block_number++;
			}

			$attributes = $block['attrs'] ?? [];

			if ($block_name) {
				$link_attributes = sw_get_link_attributes_for_block(
					$block_name,
					$attributes
				);

				foreach ($link_attributes as $link_attribute) {
					$link    = $link_attribute['link'];
					$post_id = isset($link['id'])
						? absint($link['id'])
						: 0;

					if (! $post_id) {
						$results[] = [
							'blockName'  => $block_name,
							'attribute'  => $link_attribute['attribute'],
							'blockNumber' => $block_number,
							'id'         => null,
							'status'     => 'no-id',
							'message'    => 'No post ID in link attribute.',
						];

						continue;
					}

					$results[] = [
						'blockName'   => $block_name,
						'attribute'   => $link_attribute['attribute'],
						'blockNumber' => $block_number,
					] + sw_get_linked_post_validation_result_for_post_id($post_id);
				}
			}

			if (! empty($block['innerBlocks'])) {
				$walk_blocks($block['innerBlocks']);
			}
		}
	};

	$walk_blocks($blocks);

	return $results;
}

function sw_get_button_link_validation_result_for_post_id(int $post_id): array {
	return sw_get_linked_post_validation_result_for_post_id($post_id);
}

function sw_get_button_link_validation_results(string $content): array {
	return sw_get_link_validation_results($content);
}

add_action('rest_api_init', function () {

	register_rest_route('theme/v1', '/linked-post-validator', [
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => function (
			WP_REST_Request $request
		): WP_REST_Response {

			$content = (string) $request->get_param('content');

			return rest_ensure_response([
				'items' => sw_get_link_validation_results($content),
			]);
		},

		'permission_callback' => function (
			WP_REST_Request $request
		): bool {

			$post_id = absint($request->get_param('postId'));

			if ($post_id) {
				return current_user_can('edit_post', $post_id);
			}

			return current_user_can('edit_posts');
		},

		'args' => [
			'content' => [
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ($value): bool {
					return is_string($value);
				},
			],

			'postId' => [
				'required' => false,
				'type'     => 'integer',
			],
		],
	]);

	register_rest_route(
		'theme/v1',
		'/linked-post-validator/post/(?P<id>\d+)',
		[
			'methods' => WP_REST_Server::READABLE,

			'callback' => function (
				WP_REST_Request $request
			): WP_REST_Response {

				$post_id = absint($request->get_param('id'));

				return rest_ensure_response(
					sw_get_linked_post_validation_result_for_post_id($post_id)
				);
			},

			'permission_callback' => function (): bool {
				return current_user_can('edit_posts');
			},

			'args' => [
				'id' => [
					'required' => true,
					'type'     => 'integer',
				],
			],
		]
	);

	/**
	 * Backwards compatibility routes.
	 */

	register_rest_route('theme/v1', '/button-link-validator', [
		'methods'             => WP_REST_Server::CREATABLE,

		'callback'            => function (
			WP_REST_Request $request
		): WP_REST_Response {

			$content = (string) $request->get_param('content');

			return rest_ensure_response([
				'items' => sw_get_link_validation_results($content),
			]);
		},

		'permission_callback' => function (
			WP_REST_Request $request
		): bool {

			$post_id = absint($request->get_param('postId'));

			if ($post_id) {
				return current_user_can('edit_post', $post_id);
			}

			return current_user_can('edit_posts');
		},
	]);

	register_rest_route(
		'theme/v1',
		'/button-link-validator/post/(?P<id>\d+)',
		[
			'methods' => WP_REST_Server::READABLE,

			'callback' => function (
				WP_REST_Request $request
			): WP_REST_Response {

				$post_id = absint($request->get_param('id'));

				return rest_ensure_response(
					sw_get_linked_post_validation_result_for_post_id($post_id)
				);
			},

			'permission_callback' => function (): bool {
				return current_user_can('edit_posts');
			},
		]
	);
});
