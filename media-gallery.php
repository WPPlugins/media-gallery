<?php
/*
Plugin Name: Image Gallery
Plugin URI: 
Description: Generate image galleries from media categories
Version: 1.3
Author: Kolja Schleich

Copyright 2015-2017

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

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Create simple image galleries in an instance
 * 
 * @package MediaGallery
 * @author Kolja Schleich
 * @version 1.3
 * @copyright 2015-2017
 * @license GPL-3
 */
class MediaGallery {
	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	private $version = '1.3';
	
	/**
	 * Plugin URL
	 *
	 * @var string
	 */
	private $plugin_url;

	
	/**
	 *Plugin path
	 *
	 * @var string
	 */
	private $plugin_path;
	
	
	/**
	 * Class Constructor
	 *
	 */
	public function __construct() {
		$this->plugin_url = esc_url(plugin_dir_url(__FILE__));
		$this->plugin_path = plugin_dir_path(__FILE__);
		
		if ( !defined( 'MEDIA_GALLERY_URL' ) ) {
			/**
			 * Plugin URL
			 *
			 * @var string
			 */
			define ( 'MEDIA_GALLERY_URL', $this->plugin_url );
		}
		if ( !defined( 'MEDIA_GALLERY_PATH' ) ) {
			/**
			 * Plugin path
			 *
			 * @var string
			 */
			define ( 'MEDIA_GALLERY_PATH', $this->plugin_path );
		}

		// Load plugin translations
		load_plugin_textdomain( 'media-gallery', false, basename(__FILE__, '.php').'/languages' );

		// add stylesheet and scripts
		add_action( 'wp_enqueue_scripts', array(&$this, 'addScripts') );
		
		// add new gallery taxonomy
		add_action( 'init', array(&$this, 'addTaxonomy') );
		
		// add shortcode and TinyMCE Button
		add_shortcode( 'media-gallery', array(&$this, 'shortcode') );
		add_action( 'init', array(&$this, 'addTinyMCEButton') );
		
		// Add actions to modify custom post meta upon publishing and editing post
		add_action( 'publish_post', array(&$this, 'updatePost') );
		add_action( 'edit_post', array(&$this, 'updatePost') );
		
		// register AJAX action to show TinyMCE Window
		add_action( 'wp_ajax_media-gallery_tinymce_window', array(&$this, 'showTinyMCEWindow') );
	}
	
	
	/**
	 * add new gallery taxonomy for grouping images
	 */
	public function addTaxonomy() {
		$labels = array(
			'name'              => __('Galleries', 'media-gallery'),
			'singular_name'     => __('Gallery', 'media-gallery'),
			'search_items'      => __('Search Galleries', 'media-gallery'),
			'all_items'         => __('All Galleries', 'media-gallery'),
			'parent_item'       => __('Parent Gallery', 'media-gallery'),
			'parent_item_colon' => __('Parent Gallery:', 'media-gallery'),
			'edit_item'         => __('Edit Gallery', 'media-gallery'),
			'update_item'       => __('Update Gallery', 'media-gallery'),
			'add_new_item'      => __('Add New Gallery', 'media-gallery'),
			'new_item_name'     => __('New Gallery Name', 'media-gallery'),
			'menu_name'         => __('Galleries', 'media-gallery')
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'query_var' => 'true',
			'rewrite' => 'true',
			'show_admin_column' => 'true',
		);

		register_taxonomy( 'gallery', 'attachment', $args );
	}
	
	
	/**
	 * get images from database
	 *
	 * @param integer $gallery ID of gallery
	 * @param integer $perpage number of image per page, -1 means no limit
	 * @param integer $current_page current page number
	 * @return object WP_Query results
	 */
	public function getImages( $gallery, $perpage = -1, $current_page = 1 ) {
		$query = new WP_Query(array(
			'posts_per_page' => intval($perpage),
			'paged' => intval($current_page),
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'tax_query' => array(
				array(
					'taxonomy' => 'gallery',
					'field' => 'term_id',
					'terms' => intval($gallery)
				)
			)
		));
		
		return $query;
	}
	
	
	/**
	 * display gallery using [media-gallery] shortcode
	 *
	 * @param array $atts shortcode attributes
	 * @return string
	 */
	public function shortcode( $atts ) {
		global $wp;
		
		$options = get_option( 'media_gallery', array() );
		
		extract(shortcode_atts(array(
			'gallery' => '',
			'perpage' => 9,
			'ncol' => 3,
			'crop' => 'false'
		), $atts ));
		
		if ( isset($wp->query_vars['paged']) )
			$current_page = max(1, intval($wp->query_vars['paged']));
		else
			$current_page = 1;
		
		// get images
		$query = $this->getImages( $gallery, $perpage, $current_page );
		$results = $query->posts;
		
		$ncol = intval($ncol);
		$num_images = count($results);
		$width = (100/$ncol) - 1;
		$margin = $ncol/(($ncol-1)*2);
		
		$out = "<div class='media-gallery'><div class='cols'>";
		$i = 0; $r = 1;
		foreach ( $results AS $item ) {
			$i++;

			$item->class = "";
			
			if ( $i == 1 + ($r-1)*$ncol ) $item->class = "firstImage";
			if ( $i % $ncol == 0 ) $item->class = "lastImage";
			
			$item->name = stripslashes($item->post_title);
			
			if ( isset( $options['resized_images'][$gallery] ) )
				$item->image = $options['resized_images'][$gallery][$item->ID];
			else
				$item->image = esc_url($item->guid);
			
			$item->caption = stripslashes(htmlspecialchars($item->post_excerpt));
			$item->description = stripslashes(htmlspecialchars($item->post_content));
			if ( $item->description != "" )
				$item->title = $item->description;
			elseif ( $item->caption != "" )
				$item->title = $item->caption;
			else
				$item->title = $item->name;
			
			$out .= "<div class='media-gallery-item ".$item->class."' style='width: ".$width."%; margin: 0 ".$margin."%;'><div class='media-gallery-image'>";
			$out .= "<a class='thickbox' href='".$item->image."' title='".$item->title."' rel='gallery-".$gallery."'><img src='".$item->image."' alt='".$item->name."' title='".$item->title."' /></a>";
			if ( $item->caption != "" )
				$out .= "<p class='media-gallery-caption'>".$item->caption."</p>";
			$out .= "</div></div>";
			
			if ( 0 == $i % $ncol ) {
				$r++;
				$out .= "</div>";
				if ( $i < count($results) ) $out .= '<div class="cols">';
			}
		}
		// the number of images on this page doesn't fit all columns
		if ( count($results) % $ncol != 0 )
			$out .= "</div>";
		
		// Create pagination links
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => '&#9668;',
			'next_text' => '&#9658;',
			'total' => $query->max_num_pages,
			'current' => $current_page,
			'add_args' => array()
		));
		
		if ( $page_links )
			$out .= "<p class='page-numbers'>".$page_links."</p>";
		
		$out .= "</div>";
		return $out;
	}
	

	/**
	 * resize image
	 *
	 * @param integer $id attachment ID
	 * @param array $dest_size associative array with keys 'width' and 'height'
	 * @return string image url
	 */
	private function resizeImage ( $id, $dest_size ) {
		$imagepath = get_attached_file( $id );
		$imageurl = wp_get_attachment_url( $id );
		
		// load image editor
		$image = wp_get_image_editor( $imagepath );
		
		// editor will return an error if the path is invalid - save original image url
		if ( is_wp_error( $image ) ) {
			return $imageurl;
		} else {
			// create destination file name
			$destination_file = $image->generate_filename( "{$dest_size['width']}x{$dest_size['height']}", dirname($imagepath) );
			//$this->destination_file = $destination_file;

			// resize only if the image does not exists
			if ( !file_exists($destination_file) ) {		
				// resize image with cropping enabled
				$image->resize( $dest_size['width'], $dest_size['height'], true );
				// save image
				$saved = $image->save( $destination_file );
				
				// return original url if an error occured
				 if ( is_wp_error( $saved ) ) {
					return $imageurl;
				}
			
				// Record the new size so that the file is correctly removed when the media file is deleted if the image is managed through WP Media
				if ( $id ) {
					$backup_sizes = get_post_meta( $id, '_wp_attachment_backup_sizes', true );

					if ( ! is_array( $backup_sizes ) ) {
						$backup_sizes = array();
					}

					$backup_sizes["resized-{$dest_size['width']}x{$dest_size['height']}"] = $saved;
					update_post_meta( $id, '_wp_attachment_backup_sizes', $backup_sizes );
				}
			}
			
			$new_img_url = dirname($imageurl) . '/' . basename($destination_file);
			
			return esc_url($new_img_url);
		}
	}
	
	
	/**
	 * get resize dimensions depending on image aspect ratio
	 *
	 * @param array $dims associative array with keys 'width' and 'height'
	 * @param float $aspect_ratio image aspect ratio
	 * @return array An associative array with width and height
	 */
	private function getResizeDimensions( $dims, $aspect_ratio ) {
		// wide image
		if ( $aspect_ratio > 1 ) {
			$new_width = $dims['width'];
			$new_height = $dims['width'] / $aspect_ratio;
		}
		// tall image
		if ( $aspect_ratio < 1 ) {
			$new_width = $dims['height'] / $aspect_ratio;
			$new_height = $dims['height'];
		}
		// squared image - set width and height to shorter side
		if ( $aspect_ratio == 1 ) {
			if ( $dims['width'] < $dims['height'] ) {
				$new_width = $new_height = $dims['width'];
			} else {
				$new_width = $new_height = $dims['height'];
			}
		}
		
		$new_dims = array('width' => intval($new_width), 'height' => intval($new_height));
		return $new_dims;
	}
	
	
	/**
	 * resize images of specified gallery
	 *
	 * @param integer $gallery ID of gallery
	 * @param boolean $force_resize
	 */
	private function resizeImages( $gallery, $force_resize = false	) {
		$options = get_option( 'media_gallery', array() );
		
		// get images
		$query = $this->getImages( $gallery );
		$results = $query->posts;
		
		if ( count($results) > 0 ) {
			$ratios = array();
			$img_dims = array();
			$width = $height = array();
			// Determine target aspect ratio of images
			foreach ( $results AS $img ) {
				// get image file
				$imagepath = get_attached_file( $img->ID );
				
				// load image
				$image = wp_get_image_editor( $imagepath );
				// get original image dimensions
				$dim = $image->get_size();
				// save image dimensions
				$img_dims[$img->ID] = $dim;
				$width[] = $dim['width'];
				$height[] = $dim['height'];
				
				// save image width/height ratio mapped to attachment ID
				$ratios[$img->ID] = strval($dim['width']/$dim['height']);			
			}
			// count the occurences of each aspect ratio
			$counts = array_count_values($ratios);
			arsort($counts);
			// get the most occuring aspect ratio
			$keys = array_keys($counts);
			$aspect_ratio = floatval($keys[0]);
			// sort widths and heights ascending
			sort($width);
			sort($height);
			
			$img_urls = array();
			foreach ( $results AS $img ) {
				$dest_size = array('width' => $width[0], 'height' => $height[0]);
				$img_urls[$img->ID] = esc_url($this->resizeImage( $img->ID, $dest_size ));				
			}
			
			// save resized image urls mapped to gallery ID
			$options['resized_images'][$gallery] = $img_urls;
		}
		
		// save array of resized image data
		update_option( 'media_gallery', $options );
	}
	
	/**
	 * get IPTC tags from image
	 *
	 * @param integer $id attachment ID
	 * @return array IPTC tags
	 */
	private function getIPTC($id) {
		$filename = get_attached_file($id);
		
		$info = array();
		$img_data = array();
			
		$size = getimagesize($filename, $info);
			
		$iptc = iptcparse($info['APP13']);
		
		return $iptc;
	}
	
	
	/**
	 * get EXIF metadata
	 *
	 * @param integer $id attachment ID
	 * @param string $section
	 * @param boolean $arrays
	 * @return array EXIF tags
	 */
	private function getEXIF($id, $section="", $arrays=true) {
		//$exif = exif_read_data(get_attached_file($id), $section, $arrays);
		$exif = exif_read_data(get_attached_file($id));
		return $exif;
	}
	
	
	/**
	 * add CSS Stylesheet and Javascript
	 */
	public function addScripts() {
		wp_enqueue_style( 'media-gallery', $this->plugin_url.'style.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'thickbox' );
	}
	
	
	/**
	 * add TinyMCE Button
	 */
	public function addTinyMCEButton() {
		// Don't bother doing this stuff if the current user lacks permissions
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;
		
		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true') {
			add_filter("mce_external_plugins", array(&$this, 'addTinyMCEPlugin'));
			add_filter('mce_buttons', array(&$this, 'registerTinyMCEButton'));
		}
	}
	/**
	 * add TinyMCE Plugin
	 *
	 * @param array $plugin_array An array of TinyMCE plugins
	 * @param array
	 */
	public function addTinyMCEPlugin( $plugin_array ) {
		$plugin_array['MediaGallery'] = $this->plugin_url.'tinymce/editor_plugin.js';
		return $plugin_array;
	}
	/**
	 * register TinyMCE Button
	 *
	 * @param array $buttons An array of TinyMCE Buttons
	 * @return array
	 */
	public function registerTinyMCEButton( $buttons ) {
		array_push($buttons, "separator", "MediaGallery");
		return $buttons;
	}
	
	/**
	 * Display the TinyMCE Window.
	 */
	public function showTinyMCEWindow() {
		require_once( $this->plugin_path . 'tinymce/window.php' );
		exit;
	}
	
	
	/**
	 * resize images when post is updated
	 */
	public function updatePost() {
		if (isset($_POST['post_ID'])) {
			// crop images of slideshows
			$post_content = $_POST['post_content'];
			if ( has_shortcode($post_content, 'media-gallery') ) {
				$pattern = get_shortcode_regex();
				if ( preg_match_all( '/'. $pattern .'/s', $post_content, $matches ) && array_key_exists( 2, $matches ) && in_array( 'media-gallery', $matches[2] ) ) {
					// filter for media-gallery shortcode
					$keys = array_keys($matches[2], 'media-gallery');
					
					// process each media-gallery shortcode
					foreach ( $keys AS $key ) {
						// parse shortcode attributes
						$instance = shortcode_parse_atts( stripslashes($matches[3][$key]) );
						// resize images
						if ( isset($instance['crop']) && $instance['crop'] == 'true' ) {
							$this->resizeImages( $instance['gallery'] );
						}
					}
				}
			}
		}
	}
}

$media_gallery = new MediaGallery();

/**
 * Display Media Gallery
 *
 * @api
 * @param integer $gallery gallery id to display
 * @param integer $perpage number of images per page
 * @param integer $ncol number of columns of gallery
 */
function media_gallery( $gallery, $perpage = 9, $ncol = 3 ) {
	echo do_shortcode("[media-gallery gallery='".intval($gallery)."' perpage='".intval($perpage)."' ncol='".intval($ncol)."']");
}
?>