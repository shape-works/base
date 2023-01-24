<?php
defined('ABSPATH') or die("No direct access");

/**
 * Custom functions for registering and getting cropped images using Fly
 */

// Register image size in Fly or default fallback
function paws_add_image_size( $size_name = '', $width = 0, $height = 0, $crop = false ) {
	if ( function_exists( 'fly_add_image_size' ) ) {
		return fly_add_image_size( $size_name, $width, $height, $crop );
	}
	else {
		return add_image_size( $size_name, $width, $height, $crop );
	}
}

// Get image tag with Fly or default fallback 
function paws_get_image( $attachment_id, $size, $crop, $attr ) {
	if ( function_exists( 'fly_get_attachment_image' ) ) {
		return fly_get_attachment_image( $attachment_id, $size, $crop, $attr );
	} else {
		return wp_get_attachment_image( $attachment_id, $size, $crop, $attr );
	}
}

/**
 * REST endpoint for getting cropped images in the block editor
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'paws/v1', '/get-resized-image-by-id/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'paws_get_cropped_image_by_id',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		}
	) );
} );

function paws_get_cropped_image_by_id( $data ) {
	$posts = get_posts( array(
		'author' => $data['id'],
	) );

	if ( empty( $posts ) ) {
		return null;
	}

	return $posts[0]->post_title;
}


/** 
 * Add path + options to image url 
 */
function add_resizing_settings_to_image_path($url, $width, $height, $crop) {

	$ext = pathinfo($url, PATHINFO_EXTENSION);// get file extension

	if($url == '') {
		// return if there was no image passed
		return;
	}

	if($ext == "svg"){
		// leave url unchanged if it's an svg
		return $url;
	} else {
		$site_domain = get_home_url();
		$url = str_replace('app/uploads/', '', parse_url($url, PHP_URL_PATH));
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
	bool $crop = true, 
	string $attributeName = 'imageUrl'
) {
	
	$image_url = isset($attributes[$attributeName]) ? $attributes[$attributeName] : false;
	$attachment_id  = attachment_url_to_postid( $image_url );
	$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true);
	$image_infos = wp_get_attachment_metadata($attachment_id);
	$image_original_width = $image_infos['height'];
	$image_original_height = $image_infos['width'];

	
	if($image_url){
		// add path + options to image url
		$resized_image_url = add_resizing_settings_to_image_path($image_url, $width, $height, $crop);
		
		// find the src url and replace it with resized url
		$pattern = '~src="' . $image_url . '"~';
		
		// add path + options for a 2x scale image url
		$resized_image_url_2x = add_resizing_settings_to_image_path($image_url, $width*2, $height*2, $crop);
		$resized_image_url_small = add_resizing_settings_to_image_path($image_url, $width*0.75, $height*0.75, $crop);

		$srcset = $resized_image_url_small . ' ' . $width * 0.75 . 'w, ' . $resized_image_url . ' ' . $width . 'w, ' . $resized_image_url_2x . ' ' . $width * 2 . 'w';


		if(!$crop){
			if($image_original_width > $image_original_height){
				$height = ($image_original_width/$image_original_height) * $image_original_width;
			} else {
				$width = ($image_original_height/$image_original_width) * $image_original_height;
			}
		}

		// compose opening img tag with srcset markup
		$img_with_srcset_attribute = 'src="' . $resized_image_url.'" 
									  srcset="'.$srcset.'" 
									  width="'.$width.'"
									  height="'.$height.'"
									  alt="' . $image_alt. '"
									  loading="lazy"';

		$content = preg_replace($pattern, $img_with_srcset_attribute, $content);

		return $content;
	} else { // no image used
		return $content;
	}
}

/** 
 * Post thumbnail return with alt tag and srcset
 */
function get_image_with_tag(
	int $attachment_id = null,
	int $width, 
	int $height,
	string $class = '', 
	bool $crop = true
){
	$image_url = wp_get_attachment_url( $attachment_id );
	$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$image_infos = wp_get_attachment_metadata($attachment_id);
	$image_original_width = $image_infos['height'];
	$image_original_height = $image_infos['width'];

	$resized_image_url = add_resizing_settings_to_image_path( $image_url, $width, $height, $crop );
	$resized_image_url_2x = add_resizing_settings_to_image_path( $image_url, $width*2, $height*2, $crop );
	$resized_image_url_small = add_resizing_settings_to_image_path($image_url, $width*0.75, $height*0.75, $crop);

	$srcset = $resized_image_url_small . ' ' . $width * 0.75 . 'w, ' . $resized_image_url . ' ' . $width . 'w, ' . $resized_image_url_2x . ' ' . $width * 2 . 'w';
	
	 

	if(!$crop){
		if($image_original_width > $image_original_height){
			$height = ($image_original_width/$image_original_height) * $image_original_width;
		} else {
			$width = ($image_original_height/$image_original_width) * $image_original_height;
		}
	}
	

	?>
	<img
		src="<?= $image_url ?>"
		srcset="<?= $srcset; ?>"
		width="<?= $width ?>"
		height="<?= $height ?>"
		alt="<?= $image_alt ?>"
		loading="lazy"
		<?php
		if( !empty($class) ) {
			?>
			class="<?= $class ?>"
			<?php
		}
		?>
	/>
	<?php
}