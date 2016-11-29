<?php global $p; ?>

<div class="project small <?php echo $p; ?>" id="project-<?php echo $post->post_name;?>">
	
		<div class="text">
			<div class="description">
				<h2 class="title"><?php the_title(); ?></h2>			
				
			</div>
			<button>View Project</button>
		</div>

		<a href="<?php the_permalink(); ?>" rel="bookmark" >
			<div class="overlay"></div>
		</a>
		<?php the_post_thumbnail("ttrust_portfolio", array('class' => 'thumb', 'alt' => ''.get_the_title().'', 'title' => ''.get_the_title().'')); ?>
	
																						
</div>
