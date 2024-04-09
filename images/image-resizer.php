<?php
/*
Plugin Name: SW updated - Fly Dynamic Image Resizer
Description: Dynamically create image sizes on the fly!
Version: 1.0.0
*/
defined('ABSPATH') or die('No script kiddies please!');
function jb_fly_images_init()
{
	// Set fly directory
	$fly_dir = get_fly_dir();

	// Retrieve fly directory and capability from filters if available
	$fly_dir = apply_filters('fly_dir_path', $fly_dir);

	// Check and create fly directory
	check_fly_dir($fly_dir);

	// Switch blog action
	add_action('switch_blog', 'jb_fly_images_blog_switched');
}

function get_fly_dir($path = '')
{
	$wp_upload_dir = wp_upload_dir();
	return $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'fly-images' . ('' !== $path ? DIRECTORY_SEPARATOR . $path : '');
}
/**
 * Create fly images directory if it doesn't already exist.
 */
function check_fly_dir($fly_dir)
{
	if (!is_dir($fly_dir)) {
		wp_mkdir_p($fly_dir);
	}
}

function delete_attachment_fly_images($attachment_id = 0)
{
	if (!function_exists('WP_Filesystem')) {
		return false;
	}

	WP_Filesystem();
	global $wp_filesystem;
	return $wp_filesystem->rmdir(get_fly_dir($attachment_id), true);
}

function get_fly_file_name($file_name, $width, $height, $crop)
{
	$file_name_only = pathinfo($file_name, PATHINFO_FILENAME);
	$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

	$crop_extension = '';
	if (true === $crop) {
		$crop_extension = '-c';
	} elseif (is_array($crop)) {
		$crop_extension = '-' . implode('', array_map(function ($position) {
			return $position[0];
		}, $crop));
	}

	return $file_name_only . '-' . intval($width) . 'x' . intval($height) . $crop_extension . '.' . $file_extension;
}

function get_fly_path($absolute_path = '')
{
	$wp_upload_dir = wp_upload_dir();
	$path          = $wp_upload_dir['baseurl'] . str_replace($wp_upload_dir['basedir'], '', $absolute_path);
	return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}

function blog_switched()
{
	global $fly_dir;
	$fly_dir = '';
	$fly_dir = apply_filters('fly_dir_path', get_fly_dir());
}

///Helpers
if (!function_exists('fly_get_attachment_image_src')) {
	function fly_get_attachment_image_src($attachment_id = 0, $size = '', $crop = null)
	{

		
		if ($attachment_id < 1 || empty($size)) {
			return array();
		}

		

		// Get the attachment image
		$image = wp_get_attachment_metadata($attachment_id);

		
		if (false !== $image && $image) {
			// Determine width and height based on size
			switch (gettype($size)) {
				case 'array':
					$width  = $size[0];
					$height = $size[1];
					break;
				default:
					return array();
			}

			// Get file path
			$fly_dir       = get_fly_dir($attachment_id);
			$fly_file_path = $fly_dir . DIRECTORY_SEPARATOR . get_fly_file_name(basename($image['file']), $width, $height, $crop); 

			// Check if file exists
			if (file_exists($fly_file_path)) {
				$image_size = getimagesize($fly_file_path);
				if (!empty($image_size)) {
					return array(
						'src'    => get_fly_path($fly_file_path),
						'width'  => $image_size[0],
						'height' => $image_size[1],
					);
				} else {
					return array();
				}
			}

			// File does not exist, lets check if directory exists
			check_fly_dir($fly_dir);

			// Get WP Image Editor Instance
			$image_path   = apply_filters(
				'fly_attached_file',
				get_attached_file($attachment_id),
				$attachment_id,
				$size,
				$crop
			);
			$image_editor = wp_get_image_editor($image_path);

			
			if (!is_wp_error($image_editor)) {
				// Create new image
				$image_editor->resize($width, $height, $crop);
				$image_editor->save($fly_file_path);

				// Trigger action
				do_action('fly_image_created', $attachment_id, $fly_file_path);

				// Image created, return its data
				$image_dimensions = $image_editor->get_size();
				return array(
					'src'    => get_fly_path($fly_file_path),
					'width'  => $image_dimensions['width'],
					'height' => $image_dimensions['height'],
				);
			}
		}

		// Something went wrong
		return array();
	}
}