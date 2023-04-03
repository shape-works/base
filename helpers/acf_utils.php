<?php
/* 
* Get Anchor ID reference from ACF block
* Usage: <?= get_acf_anchor_id($block) ?> 
* Exclusive to PHP ACF block files 
*/
function get_acf_anchor_id($block){
	if(isset($block)){
		$block_id = array_key_exists('anchor', $block) ? $block['anchor'] : '';

		if($block_id !== ''){
			return ' id="'.$block_id.'"';
		}
	}
}