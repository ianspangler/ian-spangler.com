<?php get_header(); ?>	
						 
		<div id="content">			
			
			<div id="pageHead">
				<?php global $post; if(is_archive() && have_posts()) :

					if (is_category()) : ?>
						<h1>category: <em><?php single_cat_title(); ?></em></h1>				
						<?php if(strlen(category_description()) > 0) echo category_description(); ?>
					<?php elseif( is_tag() ) : ?>
						<h1><?php single_tag_title(); ?></h1>
					<?php elseif (is_day()) : ?>
						<h1>archive <?php the_time('M j, Y'); ?></h1>
					<?php elseif (is_month()) : ?>
						<h1>archive <?php the_time('F Y'); ?></h1>
					<?php elseif (is_year()) : ?>
						<h1>archive <?php the_time('Y'); ?></h1>
					<?php elseif (isset($_GET['paged']) && !empty($_GET['paged'])) : ?>
						<h1>archive</h1>
					<?php endif; ?>

				<?php endif; ?>
			</div>
				
			<?php $c=0; ?>				
			<?php while (have_posts()) : the_post(); ?>
				<?php $p_class = ($c==0) ? "box first" : "box"; ?>			    
				<div <?php post_class($p_class); ?>>		
					<div class="inside">															
						<h1><a href="<?php the_permalink() ?>" rel="bookmark" ><?php the_title(); ?></a></h1>
						<div class="meta clearfix">
							<?php $post_show_author = of_get_option('ttrust_post_show_author'); ?>
							<?php $post_show_date = of_get_option('ttrust_post_show_date'); ?>
							<?php $post_show_category = of_get_option('ttrust_post_show_category'); ?>
							<?php $post_show_comments = of_get_option('ttrust_post_show_comments'); ?>
										
							<?php if($post_show_author || $post_show_date || $post_show_category){ _e('Posted ', 'themetrust'); } ?>					
							<?php if($post_show_author) { _e('by ', 'themetrust'); the_author_posts_link(); }?>
							<?php if($post_show_date) { _e('on', 'themetrust'); ?> <?php the_time( 'M j, Y' ); } ?>
							<?php if($post_show_category) { _e('in', 'themetrust'); ?> <?php the_category(', '); } ?>
							
							
						</div>						
						
						<?php if(has_post_thumbnail()) : ?>
							<?php if(of_get_option('ttrust_post_featured_img_size')=="large") : ?>											
				    			<a href="<?php the_permalink() ?>" rel="bookmark" ><?php the_post_thumbnail('ttrust_post_thumb_big', array('class' => 'postThumb', 'alt' => ''.get_the_title().'', 'title' => ''.get_the_title().'')); ?></a>		    	
							<?php else: ?>
								<a href="<?php the_permalink() ?>" rel="bookmark" ><?php the_post_thumbnail('ttrust_post_thumb_small', array('class' => 'postThumb alignleft', 'alt' => ''.get_the_title().'', 'title' => ''.get_the_title().'')); ?></a>
							<?php endif; ?>
						<?php endif; ?>															
						
						<?php the_excerpt(); ?>
						<?php more_link(); ?>
					</div>																				
			    </div>				
			
			<?php endwhile; ?>
			
			<?php get_template_part( 'part-pagination'); ?>
					    	
		</div>		
		<?php get_sidebar(); ?>				
	
		
<?php get_footer(); ?>

<script type="text/javascript">
	var show_intro = '<?php echo $_SESSION["intro"]; ?>';
</script>