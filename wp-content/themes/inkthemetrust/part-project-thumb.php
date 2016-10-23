<?php global $p; ?>

<div class="project small <?php echo $p; ?>" id="project-<?php echo $post->post_name;?>">
	
		<div class="text">
			<div class="description">
				<h2 class="title"><?php the_title(); ?></h2>			
				<!--<?php if(!empty($post->post_excerpt)) { ?>		     
					<?php the_excerpt(); ?>
				<?php } ?>-->
			</div>
			<button>View Project</button>
		</div>

		<div class="overlay"></div>
		<?php the_post_thumbnail("ttrust_portfolio", array('class' => 'thumb', 'alt' => ''.get_the_title().'', 'title' => ''.get_the_title().'')); ?>
	
		<a href="<?php the_permalink(); ?>" rel="bookmark" ></a>																					
</div>
