<?php
defined('ABSPATH') or die("No direct access");

/**
 * Serve resized images if URL starts with images/
 */

add_action( 'init', function () {
	
	if (preg_match("~^\/images\/~", $_SERVER['REQUEST_URI'])) {

		$image_url = $_SERVER['REQUEST_URI'];

		// remove "/images/"
		$image_url_minus_image_path = str_replace("/images/", "", $image_url);

		// save everything up to the next "/" to a var
		$options_string = substr($image_url_minus_image_path, 0, strpos($image_url_minus_image_path, "/"));

		// explode the ","-separated $options_string into array of strings
		$options_string_array = explode(",", $options_string);

		// explode each "="-separated option string into a key/value pair
		$options_key_value_array = array();
		foreach ($options_string_array as $pair) {
			list($key, $value) = explode("=", $pair);
			// create array of the key/value pairs
			array_key_exists($key, $options_key_value_array) ? '' :
			$options_key_value_array[$key] = $value;
		}

		// re-add "/app/uploads/" to the image url (minus "/images/width=3000...etc/")
		$image_url_minus_options = str_replace("/images/".$options_string."/", "", $image_url);
		$image_directory_and_filename = "/app/uploads/" . $image_url_minus_options;
		
		// reconstruct image url
		$original_image_url = 'http://' . $_SERVER['HTTP_HOST'] . $image_directory_and_filename;
		
		// get image ID from its url
		$image_id = attachment_url_to_postid($original_image_url);
		
		// get width, height & crop values
		$width = array_key_exists('width', $options_key_value_array) ? $options_key_value_array['width'] : 0;
		$height = array_key_exists('height', $options_key_value_array) ? $options_key_value_array['height'] : 0;
		$crop = array_key_exists('crop', $options_key_value_array) && $options_key_value_array['crop'] == '1' ? true : false;
		
		// run function that uses Fly to get or generate resized image
		$fly_image = fly_get_attachment_image_src($image_id, array( $width, $height ), $crop);

		// get Fly image url
		$fly_image_url = array_key_exists('src', $fly_image) ? $fly_image['src'] : '';

		// turn Fly image url into associative array (scheme, host, path, query)
		$fly_image_url_parsed = parse_url($fly_image_url);
		
		// construct the path to the Fly image
		$trimmed_abspath = substr(ABSPATH, 0, -3); // trim 'wp/' subfolder from ABSPATH
		$fly_image_path = $trimmed_abspath . $fly_image_url_parsed['path'];

		$type = mime_content_type($fly_image_path);

		header('Content-Type:'.$type);
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 365 * 10))); // 10 years

		readfile($fly_image_path);
		//output the file stream
		
		exit;

	}
}, 11);
