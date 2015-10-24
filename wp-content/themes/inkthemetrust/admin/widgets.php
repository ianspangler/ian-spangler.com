<?php

$ttrust_theme_name = "Ink";


/* ///////////////////////////////////////////////////////////////////// 
//  Define Widgetized Areas
/////////////////////////////////////////////////////////////////////*/



register_sidebar(array(
	'name' => 'Sidebar',
	'id' => 'sidebar',
	'description' => __('This is the default widget area for the sidebar. This will be displayed if the other sidebars have not been populated with widgets.', 'themetrust'),
	'before_widget' => '<div id="%1$s" class="%2$s sidebarBox widgetBox">',
	'after_widget' => '</div>',
	'before_img' => '<img src="',
	'after_img' => '" />',
	'before_title' => '<div class="title_text"><h3>',
	'after_title' => '</h3></div>',
	'before_subtitle' => '<h5>',
	'after_subtitle' => '</h5></div>'
));

register_sidebar(array(
	'name' => 'Page Sidebar',
	'id' => 'sidebar_pages',
	'description' => __('Widget area for the sidebar on pages.', 'themetrust'),
	'before_widget' => '<div id="%1$s" class="%2$s sidebarBox widgetBox">',
	'after_widget' => '</div>',
	'before_img' => '<img src="',
	'after_img' => '" />',
	'before_title' => '<div class="title_text"><h3>',
	'after_title' => '</h3></div>',
	'before_subtitle' => '<h5>',
	'after_subtitle' => '</h5></div>'
));

register_sidebar(array(
	'name' => 'Home Page Sidebar',
	'id' => 'sidebar_home',
	'description' => __('Widget area for the home page sidebar.', 'themetrust'),
	'before_widget' => '<div id="%1$s" class="%2$s sidebarBox widgetBox">',
	'after_widget' => '</div>',
	'before_img' => '<img src="',
	'after_img' => '" />',
	'before_title' => '<h3>',
	'after_title' => '</h3>'
));

register_sidebar(array(
	'name' => 'Post Sidebar',
	'id' => 'sidebar_posts',
	'description' => __('Widget area for the sidebar on posts.', 'themetrust'),
	'before_widget' => '<div id="%1$s" class="%2$s sidebarBox widgetBox">',
	'after_widget' => '</div>',
	'before_img' => '<img src="',
	'after_img' => '" />',
	'before_title' => '<h3>',
	'after_title' => '</h3>',

));

register_sidebar(array(
	'name' => 'Projects Sidebar',
	'id' => 'sidebar_projects',
	'description' => __('Widget area for the sidebar on projects.', 'themetrust'),
	'before_widget' => '<div id="%1$s" class="%2$s sidebarBox widgetBox">',
	'after_widget' => '</div>',
	'before_img' => '<img src="',
	'after_img' => '" />',
	'before_title' => '<h3>',
	'after_title' => '</h3>'
));



/* Allow widgets to use shortcodes */
add_filter('widget_text', 'do_shortcode');


/*/////////////////////////////////////////////////////////////////////
// About/Bio Widget
/////////////////////////////////////////////////////////////////////*/

class TTrust_About extends WP_Widget {
	
	function TTrust_About() {
		global $ttrust_theme_name, $ttrust_version, $options;
		$widget_ops = array('classname' => 'ttrust_about', 'description' => __('Show a short bio with a pic.', 'themetrust'));

		$this->WP_Widget('ttrust_about', $ttrust_theme_name.' '.__('About Me', 'themetrust'), $widget_ops);
	}

	public function widget( $args, $instance ) {

		global $ttrust_theme_name, $options;
	
		ob_start();
		extract($args);

		//$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$title = apply_filters('widget_title', empty($instance['title']) ? 'About Me' : $instance['title']);
		$subtitle = apply_filters('widget_subtitle', empty($instance['subtitle']) ? 'Creative Technologist' : $instance['subtitle']);
		$image = apply_filters( 'widget_image', empty( $instance['image'] ) ? '' : $instance['image'], $instance );
		
		$text = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance );
		///echo $args['before_widget'];

		echo $before_widget;
		if ( ! empty( $image ) ) {
			echo $before_img . $image . $after_img;
		}
		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
			//echo $before_subtitle . $subtitle . $after_subtitle;
		} ?>
			<div class="textwidget"><?php echo !empty( $instance['filter'] ) ? wpautop( $text ) : $text; ?></div>
		<?php
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['subtitle'] = strip_tags($new_instance['subtitle']);
		$instance['image'] = strip_tags($new_instance['image']);
		
		if ( current_user_can('unfiltered_html') )
			$instance['text'] =  $new_instance['text'];
		else
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
		$instance['filter'] = ! empty( $new_instance['filter'] );
		return $instance;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'subtitle' => '', 'text' => '', 'image' => '' ) );
		$title = strip_tags($instance['title']);
		$subtitle = strip_tags($instance['subtitle']);
		$text = esc_textarea($instance['text']);
		$image = $instance['image'];
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('subtitle'); ?>"><?php _e('Subtitle:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('subtitle'); ?>" name="<?php echo $this->get_field_name('subtitle'); ?>" type="text" value="<?php echo esc_attr($subtitle); ?>" /></p>


		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>
		<p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs'); ?></label></p>
		
		<p><label for="<?php echo $this->get_field_id('image'); ?>"><?php _e('Image URL:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>" type="text" value="<?php echo esc_attr($image); ?>" /></p>


<?php
	}
}

register_widget('TTrust_About');


/*///////////////////////////////////////////////////////////////////// 
//  Recent Posts
/////////////////////////////////////////////////////////////////////*/

class TTrust_Recent_Posts extends WP_Widget {

	function TTrust_Recent_Posts() {
		global $ttrust_theme_name, $ttrust_version, $options;
		$widget_ops = array('classname' => 'ttrust_recent_posts', 'description' => __('Display recent posts from any category.', 'themetrust'));
		$this->WP_Widget('ttrust_recent_posts', $ttrust_theme_name.' '.__('Recent Posts', 'themetrust'), $widget_ops);
	}

	function widget($args, $instance) {
	
		global $ttrust_theme_name, $options;
	
		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? 'Recent Posts' : $instance['title']);
		//$image	= empty($instance['image']) ? '' : $instance['image'];
		$subtitle = apply_filters('widget_subtitle', empty($instance['subtitle']) ? 'Creative Technologist' : $instance['subtitle']);
		
		$image = "";

		if ( !$number = (int) $instance['number'] )
			$number = 10;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 10 )
			$number = 10;
			
		$rp_cat = $instance['rp_cat'];			 

		$r = new WP_Query(array('cat' => $rp_cat, 'showposts' => $number, 'nopaging' => 0, 'post_status' => 'publish', 'ignore_sticky_posts' => 1));
		if ($r->have_posts()) :
?>				
			<?php echo $before_widget; ?>
			<?php echo $before_img . $image . $after_img; ?>
			<?php echo $before_title . $title . $after_title; ?>
			<!--<?php echo $before_subtitle . $subtitle . $after_subtitle; ?>-->			
		
			<ul class="widgetList">
				<?php  while ($r->have_posts()) : $r->the_post(); ?>
				<li class="clearfix">
					<?php if(has_post_thumbnail()) : ?>
						<a class="thumb" href="<?php the_permalink() ?>" rel="bookmark" ><?php the_post_thumbnail('ttrust_post_thumb_tiny', array('class' => 'postThumb', 'alt' => ''.get_the_title().'', 'title' => ''.get_the_title().'')); ?></a>					
					<?php endif; ?>
					<p class="title"><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?> </a></p>
					<span class="meta"><?php the_time(get_option('date_format')); ?> </span>
				</li>
				<?php endwhile; ?>
			</ul>
				
			<?php echo $after_widget; ?>
		
		
<?php
			wp_reset_query();  
		endif;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['rp_cat'] = $new_instance['rp_cat'];		

		return $instance;
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : 'Recent Posts';

		if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
			$number = 5;
			
		if (isset($instance['rp_cat'])) :	
			$rp_cat = $instance['rp_cat'];
		endif;
		
		
		if (isset($instance['show_post'])) :	
			$show_post = $instance['show_post'];
		endif;
		

		$pn_categories_obj = get_categories('hide_empty=0');
		$pn_categories = array(); ?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'themetrust'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('rp_cat'); ?>"><?php _e('Category', 'themetrust'); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('rp_cat'); ?>" name="<?php echo $this->get_field_name('rp_cat'); ?>">
			<option value=""><?php _e('All', 'themetrust'); ?></option>
			<?php foreach ($pn_categories_obj as $pn_cat) {				
				echo '<option value="'.$pn_cat->cat_ID.'" '.selected($pn_cat->cat_ID, $rp_cat).'>'.$pn_cat->cat_name.'</option>';
			} ?>
		</select></p>	

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts:', 'themetrust'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /><br />
		<small><?php _e('10 max', 'themetrust'); ?></small></p>
<?php
	}
}

register_widget('TTrust_Recent_Posts');


/*///////////////////////////////////////////////////////////////////// 
//  Twitter
/////////////////////////////////////////////////////////////////////*/


class TTrust_Twitter extends WP_Widget {
 
	function TTrust_Twitter() {
	
		global $ttrust_theme_name, $ttrust_version, $options;
        
        $widget_ops = array('classname' => 'widget_ttrust_twitter', 'description' => 'Display latest tweets.');

		$this->WP_Widget('ttrust_twitter', $ttrust_theme_name.' '.__('Twitter', 'themetrust'), $widget_ops);


    }
 
    function widget($args, $instance) {
    
    	global $ttrust_theme_name, $ttrust_version, $options;
       
        extract( $args );
        
        $title	= empty($instance['title']) ? 'Latest Tweets' : $instance['title'];
        $subtitle	= empty($instance['subtitle']) ? 'Creative Technologist' : $instance['subtitle'];
        $image	= '';

        $user	= $instance['user'];        
        $label	= empty($instance['twitter_label']) ? 'Follow' : $instance['twitter_label'];
        if ( !$nr = (int) $instance['twitter_count'] )
			$nr = 5;
		else if ( $nr < 1 )
			$nr = 1;
		else if ( $nr > 15 )
			$nr = 15;
 
        ?>
			<?php echo $before_widget; ?>
				<?php echo $before_img . $image . $after_img; ?>
				<?php echo $before_title . $title . $after_title; ?>
				<!--<?php echo $before_subtitle . $subtitle . $after_subtitle; ?>-->
								
				<div id="twitterBox" class="clearfix"></div>

    			<script type="text/javascript">
 					//<![CDATA[
					jQuery(document).ready(function() {
						jQuery("#twitterBox").getTwitter({
							userName: '<?php echo $user; ?>',
							numTweets: '<?php echo $nr; ?>',
							loaderText: "Loading tweets...",
							slideIn: false,
							showHeading: false,
							headingText: "",
							showProfileLink: false
						});
					});
					//]]>    			
    			</script>				
				
				<?php if($label) : ?>
                <p class="twitterLink"><a class="action" href="http://twitter.com/<?php echo $user; ?>"><span><?php echo $label; ?></span></a></p>
                <?php endif; ?>
 
			<?php echo $after_widget; ?>
        <?php
    }

    function update($new_instance, $old_instance) {  
    
    	$instance['title'] = strip_tags($new_instance['title']);
    	$instance['user'] = strip_tags($new_instance['user']);    
    	$instance['twitter_label'] = strip_tags($new_instance['twitter_label']);
    	$instance['twitter_count'] = (int) $new_instance['twitter_count'];
                  
        return $new_instance;
    }
 
    function form($instance) {
    
    	global $ttrust_theme_name, $ttrust_version, $options;
        
		$instance	= wp_parse_args( (array) $instance, array( 'title' => '', 'user' => '', 'twitter_link' => '', 'twitter_label' => '', 'twitter_count' => '') );
		$title		= empty($instance['title']) ? 'Latest Tweets' : $instance['title'];
		$user		= $instance['user'];		
		$label		= $instance['twitter_label'];
		if (!$count = (int) $instance['twitter_count']) $count = 5;
?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'themetrust'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
			</label>
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id('user'); ?>"><?php _e('Username:', 'themetrust'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('user'); ?>" name="<?php echo $this->get_field_name('user'); ?>" type="text" value="<?php echo esc_attr($user); ?>" />
			</label>
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id('twitter_count'); ?>"><?php _e('Number of tweets:', 'themetrust'); ?></label>
			<input id="<?php echo $this->get_field_id('twitter_count'); ?>" name="<?php echo $this->get_field_name('twitter_count'); ?>" type="text" value="<?php echo $count; ?>" size="3" /><br />
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id('twitter_label'); ?>"><?php _e('Follow Link label:', 'themetrust'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('twitter_label'); ?>" name="<?php echo $this->get_field_name('twitter_label'); ?>" type="text" value="<?php echo esc_attr($label); ?>" />
			</label>
		</p>
		
<?php
	}

}
 
register_widget('TTrust_Twitter');



/*///////////////////////////////////////////////////////////////////// 
//  Flickr
/////////////////////////////////////////////////////////////////////*/

class TTrust_Flickr extends WP_Widget {
 
	function TTrust_Flickr() {
		global $ttrust_theme_name, $ttrust_version, $options;
        $widget_ops = array('classname' => 'widget_ttrust_flickr', 'description' => 'Display flickr photos.');
		$this->WP_Widget('ttrust_flickr', $ttrust_theme_name.' '.__('Flickr', 'themetrust'), $widget_ops);
    
    }
 
    function widget($args, $instance) {
    
    	global $options;

        extract( $args );
        
        $title	= empty($instance['title']) ? 'Flickr' : apply_filters('widget_title', $instance['title']);
        $user	=  $instance['user'];
        
        if ( !$nr = (int) $instance['flickr_nr'] )
			$nr = 6;
		else if ( $nr < 1 )
			$nr = 3;
		else if ( $nr > 15 )
			$nr = 15;
 
        ?>
			<?php echo $before_widget; ?>

				<?php echo $before_title . $title . $after_title; ?>
				<!--<?php echo $before_subtitle . $subtitle . $after_subtitle; ?>-->
				
    			<div id="flickrBox" class="clearfix"></div>

    			<script type="text/javascript">
 					//<![CDATA[
					jQuery(window).load(function($){						 			
    					jQuery('#flickrBox').jflickrfeed({
							limit: <?php echo $nr; ?>,
							qstrings: {
								id: '<?php echo $user; ?>'
							},
							itemTemplate:
							'<div class="flickrImage">' +
								'<a href="{{link}}" title="{{title}}">' +
									'<img src="{{image_s}}" alt="{{title}}" />' +
								'</a>' +
							'</div>'
						});
					});
					//]]>    			
    			</script>
 
			<?php echo $after_widget; ?>
        <?php
    }

    function update($new_instance, $old_instance) {  
    
    	$instance['title'] = strip_tags($new_instance['title']);
    	$instance['user'] = strip_tags($new_instance['user']);
    	$instance['flickr_nr'] = (int) $new_instance['flickr_nr'];
                  
        return $new_instance;
    }
 
    function form($instance) {
    
    	global $options;
        
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'user' => '', 'flickr_nr' => '') );
		$title = strip_tags($instance['title']);
		$user = $instance['user'];
		if (!$nr = (int) $instance['flickr_nr']) $nr = 6;
?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'themetrust'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('user'); ?>"><?php _e('Flickr ID:', 'themetrust'); ?>
			<input class="widefat" id="<?php echo $this->get_field_id('user'); ?>" name="<?php echo $this->get_field_name('user'); ?>" type="text" value="<?php echo esc_attr($user); ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('flickr_nr'); ?>"><?php _e('Number of photos:', 'themetrust'); ?></label>
			<input id="<?php echo $this->get_field_id('flickr_nr'); ?>" name="<?php echo $this->get_field_name('flickr_nr'); ?>" type="text" value="<?php echo $nr; ?>" size="3" /><br />
			<small><?php _e('(15 max)'); ?></small>
		</p>
		
<?php
	}

}
 
register_widget('TTrust_Flickr');