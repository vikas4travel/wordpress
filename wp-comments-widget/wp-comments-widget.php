<?php
/*
Plugin Name: WP Comments Widget
Plugin URI: 
Description: WP Comments Widget plugin for WordPress.
Version: 0.1
Author: vikas sharma
Author URI: 
License: GPL2+
Text Domain: wp-comments-widget
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
 * Class for custom widget.
 * A widget which shows up to 1 post with the highest comment count by the selected author AND up to 1 most recent comment by the same author AND the author's gravatar.
 * Display the post title, post featured image thumbnail, and author name. The post title and image should link to the post. For the comment, include the first 200 characters of the comment and link the text to the comment.
 * If the Co-Authors Plus plugin is enabled, and the author has a Guest Author profile, the widget should pull the author's name and image from their Guest Author profile
 * If placed in a post's sidebar, make sure the current post is not included in the list (but comments from the current post are OK)
 *
 * @since 2015-10-15.
 * @version 2015-10-15 Vikas Sharma - PMCVIP-242
 */

class wp_comments_widget extends WP_Widget {
	
	function __construct() {
		parent::__construct(
			'wp_comments_widget', //Base ID
			__('WP Comments Widget', 'wp_comments_widget'), // Widget Name
			array('description' => __('Comments widget to show up to 1 post with the highest comment count', 'wp_comments_widget'))); // Widget description
	}
	

	/**
	* Creating widget front-end
	*
	* @since 2015-10-15.
	* @version 2015-10-15 Vikas Sharma - PMCVIP-242
	*/
	public function widget($args, $instance) {
		
		global $post;
		
		$currentID	=	(is_single()) ? get_the_ID() : 0;
		
		$title = apply_filters('widget_title', $instance['title']);
		
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		
		if (!empty($title)) {
			echo $args['before_title'] . $title . $args['after_title'];
		}	
		
		if(is_numeric($instance['author'])) {
		
			$query_args	=	array(
				'author' 		=> $instance['author'],
				'posts_per_page'=> 1,
				'post__not_in'	=> array($currentID),
				'orderby' 		=> 'comment_count',
				'order'   		=> 'DESC'
			);
		} else {
			
			$query_args	=	array(
				'author_name' 	=> $instance['author'],
				'posts_per_page'=> 1,
				'post__not_in'	=> array($currentID),
				'orderby' 		=> 'comment_count',
				'order'   		=> 'DESC'
			);
			
		}
		
		$listings = get_transient('wp_comments_widget'.$currentID);
		if ($listings === false) {
		
			$listings = new WP_Query( $query_args );
			set_transient('wp_comments_widget'.$currentID, $listings, 60);
		}
		
		
		// Thumb. image size.
		add_image_size( 'custom-size', 300, 187, false );
		
		if($listings->found_posts > 0) {
			
			echo '<ul class="wp_comments_widget">';
			
			while ($listings->have_posts()) {
				$listings->the_post();
				$image = (has_post_thumbnail($post->ID)) ? get_the_post_thumbnail($post->ID, 'custom-size') : '<div class="noThumb"></div>';
				$listItem = '<li>';
				$listItem .= '<a href="' . get_permalink() . '">';
				$listItem .= $image . get_the_title() . '</a>';
				$listItem .= '<br /><span>Added ' . get_the_date() . '</span>';
				$listItem .= '<span> By ' . get_the_author() . '</span></li>';
				$listItem .= '<span class="comments-widget-comment">' . $this->getComments($instance['author'], $post->ID) . '</span></li>';
				
				echo $listItem;
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p style="padding:25px;">No listing found</p>';
		}		
		
		echo $args['after_widget'];
	}
    
	/**
	* Get Latest Post and comment by same author. 
	*
	* @since 2015-10-15.
	* @version 2015-10-15 Vikas Sharma - PMCVIP-242
	*/
	public function getComments($author_id, $post_id) {
		
		if( ! is_numeric( $author_id ) ) {
			
			// if a guest author is selected in admin area, $author_id will contain author's name. 
			// find coauthor details
			$coauthors 		= get_coauthors( $post_id );
			$coauthor_email = $coauthors[0]->user_email;
			$author_avatar  = get_avatar( $coauthor_email, 32 );
			$coauthor_name  = $coauthors[0]->display_name;
			
			$args = array(
				'number' => 1,
				'post_id' => $post_id,
				'author_name' => $author_id,
				'status' => 'approve',
				'orderby' => 'comment_date',
				'order'   => 'DESC'
				
			);
			
		} else {
			$author_avatar = get_avatar( $author_id, 32 );
			
			$args = array(
				'number' => 1,
				'post_id' => $post_id,
				'user_id' => $author_id,
				'status' => 'approve',
				'orderby' => 'comment_date',
				'order'   => 'DESC'
			);
		}
		
		$latest_comment = get_comments( $args );
		
		$comment_html = "";
		
		if( $latest_comment ) foreach( $latest_comment as $comment ) {
			
			$date = strtotime($comment->comment_date);
			$date = date('M d, Y');
			
			$comment = $comment->comment_content;
			if( strlen( $comment ) >200 ) $comment = substr( $comment, 0, 200 )."...";
			
			
			if( isset( $coauthor_name ) && $coauthor_name ) {
				$author_name = $coauthor_name;
			} else {
				$author_name = $comment->comment_author;
			}	
			
			$comment_html .= 'latest comment: <br />&nbsp;&nbsp; by: ' .$author_name .'&nbsp;'. $author_avatar
						  . '<p> <a href="' .get_comments_link( $post_id ) .'"> '. $comment .'</a></p>';
		}
		return $comment_html;
	}
		
	
	/**
	* Widget Backend 
	*
	* @since 2015-10-15.
	* @version 2015-10-15 Vikas Sharma - PMCVIP-242
	*/
	public function form($instance) {
		
		
		if (isset($instance['title'])) {
			$title = $instance['title'];
		} else {
			$title = __('New title', 'wp_comments_widget');
		}
		
		if ( isset( $instance['author'] ) ) {
			$author = $instance['author'];
		} else {
			$author = null;
		}
				
		// Get the list of authors
		$authors_args  = array( 'role' => 'Author' );
		$authors_query = new WP_User_Query($authors_args);
		$authors 	   = $authors_query->get_results();
		
		// Get list of Guest authors
		$guest_args	 = array(	'post_type' => 'guest-author', 'posts_per_page' => -1 );
		$listings  	 = new WP_Query($guest_args);
		
		$guest_authors = array();
		while ($listings->have_posts()) {
			$listings->the_post();
			$guest_authors[] = get_the_title(); 
		}	
		
		// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('author'); ?>"><?php _e('Select Author'); ?></label>

				<select name="<?php echo $this->get_field_name( 'author' ); ?>" id="<?php echo $this->get_field_id( 'author' ); ?>">
				<?php
					foreach($authors as $a ) {
						
						if($a->data->ID == $instance['author']) {
							echo '<option value="'.$a->data->ID.'" selected = "selected" > '.$a->data->display_name.' </option>';
						} else {
							echo '<option value="'.$a->data->ID.'" > '.$a->data->display_name.' </option>';
						}
					}
					foreach($guest_authors as $guest_author_name ) {
						
						if($guest_author_name == $instance['author']) {
							echo '<option value="'.$guest_author_name.'" selected = "selected" > '.$guest_author_name.' (Guest Author)</option>';
						} else {
							echo '<option value="'.$guest_author_name.'" > '.$guest_author_name.' (Guest Author)</option>';
						}
					}
					
				?>
				</select>
		</p>
		
		<?php
	}
	
    
	/**
	* Updating widget replacing old instances with new
	*
	* @since 2015-10-15.
	* @version 2015-10-15 Vikas Sharma - PMCVIP-242
	*/
    public function update($new_instance, $old_instance)
    {
        $instance          = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['author'] = (!empty($new_instance['author'])) ? sanitize_text_field($new_instance['author']) : ''; 
		return $instance;
    } 
} // Class wp_comments_widget ends here


/**
* Register and load the widget
*
* @since 2015-10-15.
* @version 2015-10-15 Vikas Sharma - PMCVIP-242
*/
function load_wp_comments_widget() {
    register_widget('wp_comments_widget');
}
add_action('widgets_init', 'load_wp_comments_widget'); 



/**
* Enqueue our stylesheet.
*
* @since 2015-10-15.
* @version 2015-10-15 Vikas Sharma - PMCVIP-242
*/
function wp_comments_widget_css() {
	
	wp_register_style( 'wp-comments-widget', plugins_url( '/css/wp-comments-widget.css', __FILE__) );
	wp_enqueue_style( 'wp-comments-widget' );
}

add_action( 'wp_enqueue_scripts', 'wp_comments_widget_css' );























