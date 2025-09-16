<?php

/**
 * Unique Media 
 * Detects duplicate media by size + md5 and prevent uploading duplicates
 * Inspired by https://github.com/mcguffin/wp-unique-media/tree/master/include/UniqueMedia/Cron
 */

// Security
if (! defined('ABSPATH')) {
	exit;
}

if (! defined('UM_SIZE_META_KEY')) define('UM_SIZE_META_KEY', 'mdd_size');
if (! defined('UM_HASH_META_KEY')) define('UM_HASH_META_KEY', 'mdd_hash');

if (! defined('UM_STATE_WARNING')) define('UM_STATE_WARNING', 'um-state-warning');
if (! defined('UM_STATE_ERROR'))   define('UM_STATE_ERROR',   'um-state-error');

$GLOBALS['um_unique_media_last'] = array(
	'size'          => null,
	'hash'          => null,
	'attachment_id' => null,
);

/**
 * Get duplicates of an attachment by stored hash.
 *
 * @param int|WP_Post $attachment
 * @return int[] post IDs
 */
function um_unique_media_get_duplicates($attachment) {
	global $wpdb;

	if (is_numeric($attachment)) {
		$attachment = get_post($attachment);
	}
	if (! $attachment) {
		return array();
	}

	$hash = get_post_meta($attachment->ID, UM_HASH_META_KEY, true);
	if (! $hash) {
		return array();
	}

	// Find any other attachment with same hash
	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id
			 FROM $wpdb->postmeta
			 WHERE post_id != %d AND meta_key = %s AND meta_value = %s",
			$attachment->ID,
			UM_HASH_META_KEY,
			$hash
		)
	);
}

/**
 * Get attachments by raw hash value.
 *
 * @param string $hash
 * @return int[] post IDs
 */
function um_unique_media_get_attachments_by_hash($hash) {
	global $wpdb;

	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
			UM_HASH_META_KEY,
			$hash
		)
	);
}

/**
 * Get IDs of attachments missing a hash meta.
 *
 * @return int[]
 */
function um_unique_media_get_unhashed_attachments() {
	global $wpdb;

	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID
			   FROM $wpdb->posts AS p
			   LEFT JOIN $wpdb->postmeta AS m
			     ON p.ID = m.post_id AND m.meta_key = %s
			  WHERE p.post_type = %s
			    AND m.meta_id IS NULL",
			UM_HASH_META_KEY,
			'attachment'
		)
	);
}

/**
 * Hash a single attachment file and update meta.
 *
 * @param int $attachment_id
 * @return array|WP_Error
 */
function um_unique_media_hash_attachment($attachment_id) {
	$wp_error = new WP_Error();
	$file = false;

	if (function_exists('wp_get_original_image_path')) {
		$file = wp_get_original_image_path($attachment_id); // WP ≥ 5.3
	} else {
		$file = get_attached_file($attachment_id); // WP < 5.3
	}

	if (! $file) {
		$wp_error->add(UM_STATE_ERROR, sprintf(__('No file attached to %d', 'wp-unique-media'), $attachment_id));
		return $wp_error;
	}
	if (! file_exists($file)) {
		$wp_error->add(UM_STATE_ERROR, sprintf(__('File %1$s of attachment %2$d does not exist', 'wp-unique-media'), $file, $attachment_id));
		return $wp_error;
	}

	$prev_hash = get_post_meta($attachment_id, UM_HASH_META_KEY, true);
	$prev_size = (int) get_post_meta($attachment_id, UM_SIZE_META_KEY, true);

	$size = filesize($file);
	$hash = md5_file($file);

	if ($prev_hash !== $hash || $prev_size !== $size) {
		if ($prev_hash || $prev_size) {
			$wp_error->add(
				UM_STATE_WARNING,
				sprintf(
					__('Attachment %1$d hashes differ from previous state. Hash (old:new) (%2$s:%3$s); Size (old:new) (%4$d:%5$d);', 'wp-unique-media'),
					$attachment_id,
					$prev_hash,
					$hash,
					$prev_size,
					$size
				)
			);
		}
		update_post_meta($attachment_id, UM_HASH_META_KEY, $hash);
		update_post_meta($attachment_id, UM_SIZE_META_KEY, $size);
	} else {
		$wp_error->add(UM_STATE_WARNING, sprintf(__('Attachment %d already hashed', 'wp-unique-media'), $attachment_id));
	}

	return array(
		'id'        => $attachment_id,
		'size'      => $size,
		'hash'      => $hash,
		'prev_size' => $prev_size,
		'prev_hash' => $prev_hash,
		'error'     => $wp_error,
	);
}

/**
 * Hash a batch of unhashed attachments (utility you can run manually if needed).
 *
 * @param int|null $time_limit Seconds to run (null = no limit).
 * @return array
 */
function um_unique_media_hash_attachments($time_limit = 5) {
	$t0       = time();
	$unhashed = um_unique_media_get_unhashed_attachments();
	$results  = array();

	while (count($unhashed)) {
		if (! is_null($time_limit) && (time() - $t0) > $time_limit) {
			break;
		}
		$attachment_id = array_pop($unhashed);
		$results[]     = um_unique_media_hash_attachment($attachment_id);
	}
	return $results;
}

/**
 * On successful file move, store size + hash as attachment meta.
 *
 * @filter update_attached_file
 */
add_filter('update_attached_file', function ($file, $attachment_id) {
	if ($file) {
		$size = @filesize($file);
		$hash = @md5_file($file);
		if ($size !== false) {
			update_post_meta($attachment_id, UM_SIZE_META_KEY, (int) $size);
		}
		if ($hash) {
			update_post_meta($attachment_id, UM_HASH_META_KEY, $hash);
		}
	}
	return $file;
}, 10, 2);

/**
 * Before upload actually happens, check for duplicates and short-circuit.
 *
 * @filter wp_handle_upload_prefilter
 * @filter wp_handle_sideload_prefilter
 */
$__um_upload_prefilter = function ($file) {
	global $wpdb;

	if (empty($file['tmp_name'])) {
		return $file;
	}

	$last_size = (int) $file['size'];
	$last_hash = md5_file($file['tmp_name']);

	$GLOBALS['um_unique_media_last']['size'] = $last_size;
	$GLOBALS['um_unique_media_last']['hash'] = $last_hash;

	// Fast pre-check: do we have *any* row with the same size stored?
	$has_same_size = absint($wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(meta_id) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d",
			UM_SIZE_META_KEY,
			$last_size
		)
	));

	if ($has_same_size) {
		// Confirm by hash
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				UM_HASH_META_KEY,
				$last_hash
			)
		);

		if ($attachment_id) {
			$GLOBALS['um_unique_media_last']['attachment_id'] = (int) $attachment_id;

			// If this is the media modal async upload, return the existing attachment JSON
			if (isset($_REQUEST['action']) && 'upload-attachment' === $_REQUEST['action']) {
				if (! $attachment = wp_prepare_attachment_for_js($attachment_id)) {
					wp_die();
				}
				$attachment['duplicate_upload'] = true;
				echo wp_json_encode(array(
					'success' => true,
					'data'    => $attachment,
				));
				exit;
			}

			// Classic media upload screen: show an error message (WP doesn't allow HTML here)
			$image = false;
			if (function_exists('wp_get_original_image_path')) {
				$image = wp_get_original_image_path($attachment_id);
			}
			if (! $image) {
				$image = get_attached_file($attachment_id);
			}

			$file['error'] = sprintf(
				/* translators: 1: attachment ID, 2: filename */
				__('Duplicate file exists: ID %1$d - "%2$s"', 'wp-unique-media'),
				$attachment_id,
				basename((string) $image)
			);
			return $file;
		}
	} else {
		// If we didn’t detect a same-size candidate, when the attachment is added, persist our precomputed size/hash.
		add_action('add_attachment', function ($attachment_id) use ($last_size, $last_hash) {
			update_post_meta($attachment_id, UM_HASH_META_KEY, $last_hash);
			update_post_meta($attachment_id, UM_SIZE_META_KEY, $last_size);
		});
	}

	return $file;
};
add_filter('wp_handle_upload_prefilter', $__um_upload_prefilter);
add_filter('wp_handle_sideload_prefilter', $__um_upload_prefilter); // for sideloads

/**
 * In the media modal: show a “Duplicates” field listing existing copies.
 *
 * @filter attachment_fields_to_edit
 */
add_filter('attachment_fields_to_edit', function ($fields, $attachment) {
	$dupes = um_unique_media_get_duplicates($attachment);
	if (empty($dupes)) {
		return $fields;
	}

	$html = '<ul>';
	foreach ($dupes as $post_id) {
		$html .= sprintf(
			'<li><a data-id="%1$d" href="%2$s">%3$s</a></li>',
			$post_id,
			esc_url(get_edit_post_link($post_id)),
			esc_html(get_the_title($post_id))
		);
	}
	$html .= '</ul>';

	$fields['wpum-duplicates'] = array(
		'label' => __('Duplicates', 'wp-unique-media'),
		'input' => 'html',
		'html'  => $html,
	);
	return $fields;
}, 10, 2);

/**
 * If a REST upload was “denied” because it’s a duplicate, return the existing
 * attachment as if it was created (201) so clients get a coherent response.
 *
 * @filter rest_request_after_callbacks
 */
add_filter('rest_request_after_callbacks', function ($response, $handler, $request) {
	$last = $GLOBALS['um_unique_media_last'];

	if (
		'/wp/v2/media' === $request->get_route()
		&& 'POST' === $request->get_method()
		&& ! empty($last['attachment_id'])
	) {
		$controller  = get_post_type_object('attachment')->get_rest_controller();
		$attachment  = get_post((int) $last['attachment_id']);
		$response    = $controller->prepare_item_for_response($attachment, $request);
		$response    = rest_ensure_response($response);
		$response->set_status(201);

		// Use the actual attachment ID found
		$response->header(
			'Location',
			rest_url(sprintf('%s/%d', $request->get_route(), (int) $last['attachment_id']))
		);
	}

	return $response;
}, 10, 3);

add_action('wp_enqueue_media', function () {
	$js_url = plugin_dir_url(__FILE__) . 'unique-media.js';
	wp_enqueue_script('unique-media-admin', $js_url, [], null, true);
	wp_localize_script('unique-media-admin', 'unique_media_admin', []);
});
