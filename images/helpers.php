<?php
defined('ABSPATH') or die("No direct access");

/**
 * Custom functions for registering and getting cropped images using Fly
 */

// Register image size in Fly or default fallback
function paws_add_image_size($size_name = '', $width = 0, $height = 0, $crop = false) {
	if (function_exists('fly_add_image_size')) {
		return fly_add_image_size($size_name, $width, $height, $crop);
	} else {
		return add_image_size($size_name, $width, $height, $crop);
	}
}

// Get image tag with Fly or default fallback 
function paws_get_image($attachment_id, $size, $crop, $attr) {
	if (function_exists('fly_get_attachment_image')) {
		return fly_get_attachment_image($attachment_id, $size, $crop, $attr);
	} else {
		return wp_get_attachment_image($attachment_id, $size, $crop, $attr);
	}
}

/**
 * REST endpoint for getting cropped images in the block editor
 */
add_action('rest_api_init', function () {
	register_rest_route('paws/v1', '/get-resized-image-by-id/(?Pid\d+)', array(
		'methods' => 'GET',
		'callback' => 'paws_get_cropped_image_by_id',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		}
	));
});

function paws_get_cropped_image_by_id($data) {
	$posts = get_posts(array(
		'author' => $data['id'],
	));

	if (empty($posts)) {
		return null;
	}

	return $posts[0]->post_title;
}

/** 
 * Add path + options to image url 
 */

function add_resizing_settings_to_image_path($url, $width, $height, $crop) {

	$ext = pathinfo($url, PATHINFO_EXTENSION); // get file extension

	if ($url == '') {
		// return if there was no image passed
		return;
	}

	if ($ext == "svg") {
		// leave url unchanged if it's an svg
		return $url;
	} else {
		$site_domain = get_home_url();
		$app_uploads_path = apply_filters('base_image_app_uploads_path', 'app/uploads/');

		$url = str_replace($app_uploads_path, '', parse_url($url, PHP_URL_PATH));

		$crop == false ? $crop = 0 : '';

		// add query string for image resizing to the url
		return $site_domain . '/images/width=' . $width . ',height=' . $height . ',crop=' . $crop . $url;
	}
}

/** 
 * 1. Replace imageUrl attribute with resized image url 
 * 2. Add srcset attribute to img tag for 2x pixel density
 */
function replace_image_url_with_resized_url_and_add_srcset(
	string $content,
	array $attributes,
	string $width,
	string $height,
	string | bool $crop = '',
	string $attributeName = 'imageObject',
	string $sizes = '',
	bool $lazy_load = true,
	bool $alwaysUseFallback = false
) {
	$image_url = '';
	$attachment_id = '';

	// Check for the imageObject attribute
	if (array_key_exists($attributeName, $attributes) && !$alwaysUseFallback) {
		$image_url = $attributes[$attributeName]['url'];
		$attachment_id = $attributes[$attributeName]['id'];

		// Fallback: derive ID from image URL if not provided
		if (!$attachment_id && !empty($attributes[$attributeName]['url'])) {
			$attachment_id = attachment_url_to_postid($attributes[$attributeName]['url']);
		}

		if ($crop !== false && array_key_exists('crop', $attributes[$attributeName])) {
			$imageCropPosition = $attributes[$attributeName]['crop'];
			$imageCropPosition = str_replace(' ', '-', $imageCropPosition);
			$crop = $imageCropPosition;
		}

		$crop == false ? $crop = 0 : '';
	} else {
		// Fallback to old logic if imageObject does not exist
		$image_url = isset($attributes["imageUrl"]) ? $attributes["imageUrl"] : false;
		$attachment_id  = $image_url ? attachment_url_to_postid($image_url) : false;

		// Further fallback to mediaUrl if imageUrl doesn't exist
		if (!$image_url) {
			$image_url = isset($attributes["mediaUrl"]) ? $attributes["mediaUrl"] : false;
			$attachment_id = $image_url ? attachment_url_to_postid($image_url) : false;
		}
	}

	if ($image_url && $attachment_id) {
		// find the src url and replace it with resized url
		$pattern = '~src="' . $image_url . '"~';

		$img_with_srcset_attribute = get_image_attributes($attachment_id, $width, $height, '', $crop, $sizes, $lazy_load);

		$content = preg_replace($pattern, $img_with_srcset_attribute, $content, 1);

		return $content;
	} else { // no image used
		return $content;
	}
}

/** 
 * Post thumbnail return with alt tag and srcset
 */

function get_image_attributes(
	int $attachment_id = null,
	int $width,
	int $height,
	string $class = '',
	string $crop = '',
	string $sizes = '',
	bool $lazy_load = true
) {
	if ($attachment_id) {
		$image_url = wp_get_attachment_url($attachment_id);
		$image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
		$image_infos = wp_get_attachment_metadata($attachment_id);

		if ($image_infos) {
			$image_original_width = $image_infos['width'];
			$image_original_height = $image_infos['height'];

			$resized_image_url = add_resizing_settings_to_image_path($image_url, $width, $height, $crop);
			$resized_image_url_2x = add_resizing_settings_to_image_path($image_url, $width * 2, $height * 2, $crop);
			$resized_image_url_small = add_resizing_settings_to_image_path($image_url, round($width * 0.75), round($height * 0.75), $crop);
			$resized_image_url_micro = add_resizing_settings_to_image_path($image_url, round($width * 0.50), round($height * 0.50), $crop);

			$srcset =  $resized_image_url_micro . ' ' .  round($width * 0.50) . 'w,';
			$srcset .= $resized_image_url_small . ' ' .  round($width * 0.75) . 'w,';
			$srcset .= $resized_image_url . ' ' . $width . 'w,';
			$srcset .= $resized_image_url_2x . ' ' . $width * 2 . 'w';

			if (!$crop) {
				if (
					$image_original_width > $image_original_height
					||
					$height == 0
				) {
					$height = ($image_original_height / $image_original_width) * $width;
				} else {
					$width = ($image_original_height / $image_original_width) * $height;
				}
			}

			$image_attributes = '
				src="' . $resized_image_url . '" 
				srcset="' . $srcset . '"
				width="' . $width . '"
				height="' . $height . '"
				alt="' . $image_alt . '"';
			$lazy_load == true ? $image_attributes .= 'loading="lazy"' : '';
			$sizes ? $image_attributes .= 'sizes="' . $sizes . '"' : '';
			$class ? $image_attributes .= 'class="' . $class . '"' : '';

			return $image_attributes;
		}
	}
}
