<?php 
/*
Plugin Name: Processing JS
Plugin URI: http://www.keyvan.net/code/processing-js/
Donate link: http://www.keyvan.net/code/processing-js/#donate
Description: Embed Processing sketches into your posts
Author: Keyvan Minoukadeh
Version: 0.5
Author URI: http://www.keyvan.net/
*/

/*
Copyright 2009 Keyvan Minoukadeh

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/ 
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
add_action('init', 'pjs_init');
function pjs_init() {
	wp_enqueue_script('processing-js', plugin_dir_url(__FILE__) . 'js/processing.min.js');
	wp_enqueue_script('processing-init', plugin_dir_url(__FILE__) .'js/init.js', array('jquery'));
}

//add_action('wp_head', 'pjs_head');
function pjs_head() {
	// test excanvas one day for IE support
	// echo '<!--[if IE]><script src="'.plugin_dir_url(__FILE__) . 'js/excanvas.js"></script><![endif]-->'."\n";
}

add_action('admin_print_scripts', 'pjs_quicktags');
function pjs_quicktags() {
	wp_enqueue_script(
		'processing-quicktags',
		plugin_dir_url(__FILE__) . 'js/quicktags.js',
		array('quicktags')
	);
}

/**********************************************
 Keep Processing code intact (taken from Raw HTML) 
 http://wordpress.org/extend/plugins/raw-html/
***********************************************/
global $pjs_raw_parts;
$pjs_raw_parts=array();

function pjs_extraction_callback($matches){
	global $pjs_raw_parts;
	$pjs_raw_parts[]=$matches[1];
	return "!RAWBLOCK".(count($pjs_raw_parts)-1)."!";
}

function pjs_extract_exclusions($text){
	global $pjs_raw_parts;
	
	$tags = array(array('<script type="application/processing">', '</script>'));

	foreach ($tags as $tag_pair){
		list($start_tag, $end_tag) = $tag_pair;
		
		//Find the start tag
		$start = stripos($text, $start_tag, 0);
		while($start !== false){
			$content_start = $start + strlen($start_tag);
			
			//find the end tag
			$fin = stripos($text, $end_tag, $content_start);
			
			//break if there's no end tag
			if ($fin == false) break;
			
			//extract the content between the tags
			$content = substr($text, $content_start,$fin-$content_start);
			
			//Store the content and replace it with a marker
			$pjs_raw_parts[]='<script type="application/processing">'.$content.'</script>';
			$replacement = "!RAWBLOCK".(count($pjs_raw_parts)-1)."!";
			$text = substr_replace($text, $replacement, $start, 
				$fin+strlen($end_tag)-$start
			 );
			
			//Have we reached the end of the string yet?
			if ($start + strlen($replacement) > strlen($text)) break;
			
			//Find the next start tag
			$start = stripos($text, $start_tag, $start + strlen($replacement));
		}
	}
	return $text;
	/*
	//The regexp version is much shorter, but it has problems with big posts : 
	return preg_replace_callback("/(?:<!--\s*start_raw\s*-->|\[RAW\])(.*?)(?:<!--\s*end_raw\s*-->|\[\/RAW\])/is", 
		"pjs_extraction_callback", $text);
	//	*/
}

function pjs_insertion_callback($matches){
	global $pjs_raw_parts;
	return $pjs_raw_parts[intval($matches[1])];
}

function pjs_insert_exclusions($text){
	global $pjs_raw_parts;
	if(!isset($pjs_raw_parts)) return $text;
	return preg_replace_callback("/!RAWBLOCK(\d+?)!/", "pjs_insertion_callback", $text);		
}

add_filter('the_content', 'pjs_extract_exclusions', 2);
add_filter('the_content', 'pjs_insert_exclusions', 1001);
?>