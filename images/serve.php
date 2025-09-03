<?php
defined('ABSPATH') or die("No direct access");

/**
 * Serve resized images if URL starts with images/
 */
add_action('init', function () {

	if (!defined('IS_BEDROCK')) {
		define('IS_BEDROCK', true);
	}

	$rewrite_regex = apply_filters('base_image_regex', "~^\/images\/~");

	if (preg_match($rewrite_regex, $_SERVER['REQUEST_URI'])) {

		$image_url = apply_filters('base_image_full_url', $_SERVER['REQUEST_URI']);

		// remove "/images/"
		$image_url_minus_image_path = str_replace("/images/", "", $image_url);

		// save everything up to the next "/" to a var
		$options_string = substr($image_url_minus_image_path, 0, strpos($image_url_minus_image_path, "/"));

		// explode the ","-separated $options_string into array of strings
		$options_string_array = explode(",", $options_string);

		// explode each "="-separated option string into a key/value pair
		$options_key_value_array = array();

		foreach ($options_string_array as $pair) {
			$parts = explode("=", $pair, 2); // Limit to 2 parts
			if (count($parts) === 2) {
				list($key, $value) = $parts;
				if (!array_key_exists($key, $options_key_value_array)) {
					$options_key_value_array[$key] = $value;
				}
			}
		}


		// re-add "/app/uploads/" to the image url (minus "/images/width=3000...etc/")
		$image_url_minus_options = str_replace("/images/" . $options_string . "/", "", $image_url);
		$uploads_base = IS_BEDROCK ? '/app/uploads/' : '/wp-content/uploads/';
		$image_directory_and_filename = $uploads_base . $image_url_minus_options;

		// reconstruct image url
		$site_domain = apply_filters('base_image_site_domain', $_SERVER['HTTP_HOST']);
		$original_image_url = 'http://' . $site_domain . $image_directory_and_filename;

		// get image ID from its url
		$image_id = attachment_url_to_postid($original_image_url);


		function parse_crop_string($crop_string) {
			$crop_string = strtolower(str_replace('-', '', $crop_string));

			$crop_mappings = array(
				//Left X + Y
				'lefttop' => array('left', 'top'),
				'topleft' => array('left', 'top'),
				'leftcenter' => array('left', 'center'),
				'centerleft' => array('left', 'center'),
				'leftbottom' => array('left', 'bottom'),
				'bottomleft' => array('left', 'bottom'),

				//Right X + Y
				'righttop'   => array('right', 'top'),
				'topright'   => array('right', 'top'),
				'rightcenter'   => array('right', 'center'),
				'centerright'   => array('right', 'center'),
				'rightbottom'   => array('right', 'bottom'),
				'bottomright'   => array('right', 'bottom'),

				//Center X + Y
				'centerbottom'   => array('center', 'bottom'),
				'bottomcenter'   => array('center', 'bottom'),
				'topcenter'   => array('center', 'top'),
				'centertop'   => array('center', 'top'),

			);

			// Check if the input string is in the mappings
			if (isset($crop_mappings[$crop_string])) {
				return $crop_mappings[$crop_string];
			}

			//Default
			return array('center', 'center');
		}


		// get width, height & crop values
		$width = array_key_exists('width', $options_key_value_array) ? $options_key_value_array['width'] : 0;
		$height = array_key_exists('height', $options_key_value_array) ? $options_key_value_array['height'] : 0;
		$crop = false;

		if (array_key_exists('crop', $options_key_value_array)) {
			if ($options_key_value_array['crop'] == '1') {
				$crop = true;
			} else if ($options_key_value_array['crop'] == '0') {
				$crop = false;
			} else if (is_string($options_key_value_array['crop'])) {
				$crop = parse_crop_string($options_key_value_array['crop']);
			}
		}

		// run function that uses Fly to get or generate resized image
		$fly_image = fly_get_attachment_image_src($image_id, array($width, $height), $crop);

		// get Fly image url
		$fly_image_url = array_key_exists('src', $fly_image) ? $fly_image['src'] : '';

		// turn Fly image url into associative array (scheme, host, path, query)
		$fly_image_url_parsed = parse_url($fly_image_url);

		// construct the path to the Fly image
		$trimmed_abspath = substr(ABSPATH, 0, -3); // trim 'wp/' subfolder from ABSPATH
		$fly_image_path = $trimmed_abspath . $fly_image_url_parsed['path'];
		$fly_image_path = apply_filters('base_fly_image_path', $fly_image_path);

		$type = mime_content_type($fly_image_path);

		header('Content-Type:' . $type);
		header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 365 * 10))); // 10 years

		readfile($fly_image_path);
		//output the file stream

		exit;
	}
}, 11);
