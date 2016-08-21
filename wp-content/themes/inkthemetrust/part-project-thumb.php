<?php global $p; ?>

<div class="project small <?php echo $p; ?>" id="project-<?php echo $post->post_name;?>" style="background-color: <?php echo get_featured_image_color(); ?>">
	
		<div class="text">
			<div class="description">
				<span class="title"><?php the_title(); ?></span>			
				<?php if(!empty($post->post_excerpt)) { ?>		     
					<?php the_excerpt(); ?>
				<?php } ?>
				<button>View Project</button>
			</div>
		</div>

		<div class="gr-overlay"></div>
		<?php the_post_thumbnail("ttrust_portfolio", array('class' => 'thumb', 'alt' => ''.get_the_title().'', 'title' => ''.get_the_title().'')); ?>
	
		<a href="<?php the_permalink(); ?>" rel="bookmark" ></a>																					
</div>
