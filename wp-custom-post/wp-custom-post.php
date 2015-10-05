<?php
/*
Plugin Name: WP Custom Post
Plugin URI: 
Description: Custom Post Type plugin for WordPress.
Version: 0.1
Author: vikas sharma
Author URI: 
License: GPL2+
Text Domain: wp-custom-post
*/

/*
    Copyright vikas sharma

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/**
 * Register our custom post type.
 */
function register_custom_post() {
	$labels = array(
		'name'               => _x( 'Custom Posts', 'custom posts', 'wp-custom-post' ),
		'singular_name'      => _x( 'Custom Post', 'custom post', 'wp-custom-post' ),
		'menu_name'          => _x( 'Custom Posts', 'custom posts', 'wp-custom-post' ),
		'name_admin_bar'     => _x( 'Custom Posts', 'custom posts', 'wp-custom-post' ),
		'add_new'            => _x( 'Add New', 'custom-post', 'wp-custom-post' ),
		'add_new_item'       => __( 'Add New Custom Post', 'wp-custom-post' ),
		'new_item'           => __( 'New Custom Post', 'wp-custom-post' ),
		'edit_item'          => __( 'Edit Custom Post', 'wp-custom-post' ),
		'view_item'          => __( 'View Custom Post', 'wp-custom-post' ),
		'all_items'          => __( 'All Custom Posts', 'wp-custom-post' ),
		'search_items'       => __( 'Search Custom Posts', 'wp-custom-post' ),
		'parent_item_colon'  => __( 'Parent Custom Posts:', 'wp-custom-post' ),
		'not_found'          => __( 'No Custom Post found.', 'wp-custom-post' ),
		'not_found_in_trash' => __( 'No Custom Post found in Trash.', 'wp-custom-post' )
	);

	$args = array(
		'labels'             => $labels,
        'description'        => __( 'Description.', 'wp-custom-post' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'custompost' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
	);

	register_post_type( 'wp-custom-post', $args );
} 
add_action( 'init', 'register_custom_post' );


/**
 * Install custom post type.
 */
function custom_post_install() {
	
    // Trigger our function that registers the custom post type
    register_custom_post();
 
    // Clear the permalinks after the post type has been registered
    flush_rewrite_rules();
 
}
register_activation_hook( __FILE__, 'custom_post_install' );


/**
 * Uninstall custom post type.
 */
function custom_post_deactivation() {
 	
    // Our post type will be automatically removed, so no need to unregister it
 
    // Clear the permalinks to remove our post type's rules
    flush_rewrite_rules();
 
}
register_deactivation_hook( __FILE__, 'custom_post_deactivation' );




function filter_where($where = '') {
	//posts in last 30 days
	$where .= " AND post_date > '" . date('Y-m-d', strtotime('-30 days')) . "'";
	return $where;
}

/**
 * Class for custom widget.
 * A widget which show up to 5 most recent posts, maximum of 30 days old, for posts of that custom post type
 * Display the post title, post featured image thumbnail, and author name. The post title and image should link to the post.
 * If placed in a post's sidebar, make sure the current post is not included in the list
 */

class wp_custom_widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wp_custom_widget', //Base ID
			__('WP Custom Widget', 'wp_custom_widget'), // Widget Name
			array('description' => __('Custom widget to show up to 5 most recent posts', 'wp_custom_widget'))); // Widget description
	}
	
	// Creating widget front-end
	public function widget($args, $instance) {
		
		$title = apply_filters('widget_title', $instance['title']);
		
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		
		if (!empty($title)) {
			echo $args['before_title'] . $title . $args['after_title'];
		}	
		
		global $post;

		
		
		$query_args =  array( 
			'posts_per_page'=> '5', 
			'post_type'		=> 'wp-custom-post',
			'post__not_in'	=> array(get_the_ID()),
			'orderby'		=> 'date',
			'order'			=> 'DESC'
		); 
		
		add_filter('posts_where', 'filter_where');
		
		$listings = new WP_Query($query_args);
		
		add_image_size( 'custom-size', 300, 187, false );
		
		if($listings->found_posts > 0) {
			
			echo '<ul class="wp_custom_post">';
			
			while ($listings->have_posts()) {
				$listings->the_post();
				$image = (has_post_thumbnail($post->ID)) ? get_the_post_thumbnail($post->ID, 'custom-size') : '<div class="noThumb"></div>';
				$listItem = '<li>';
				$listItem .= '<a href="' . get_permalink() . '">';
				$listItem .= $image . get_the_title() . '</a>';
				$listItem .= '<span>Added ' . get_the_date() . '</span>';
				$listItem .= '<span>By ' . get_the_author() . '</span></li>';
				
				echo $listItem;
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p style="padding:25px;">No listing found</p>';
		}		
		
		echo $args['after_widget'];
	}
    
	// Widget Backend 
	public function form($instance) {
		
		if (isset($instance['title'])) {
			$title = $instance['title'];
		} else {
			$title = __('New title', 'wp_custom_widget');
		}
		
		// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php
	}
    
    // Updating widget replacing old instances with new
    public function update($new_instance, $old_instance)
    {
        $instance          = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
} // Class wp_custom_widget ends here


// Register and load the widget
function load_wp_custom_widget() {
    register_widget('wp_custom_widget');
}
add_action('widgets_init', 'load_wp_custom_widget');

// Enqueue our stylesheet.
function wp_custom_post_css() {
	wp_enqueue_style( 'wp-custom-post', plugins_url('/css/wp-custom-post.css', __FILE__), false, '1.0.0', 'all');
}

add_action( 'wp_enqueue_scripts', 'wp_custom_post_css' );





