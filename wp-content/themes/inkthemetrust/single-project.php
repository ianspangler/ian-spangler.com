<?php get_header(); ?>		
		
		<div id="content" class="projectPage clearfix<?php if ($_SESSION['intro'] == 'Y'): ?> faded_out<?php endif; ?>">			
			<div id="pageHead">
				<h1><?php the_title(); ?></h1>
				<div class="projectNav clearfix">
					<div class="previous <?php if(!get_previous_post()){ echo 'inactive'; }?>">
						<?php next_post_link('%link', '&larr; previous'); ?>
					</div>

					<div class="next <?php if(!get_next_post()){ echo 'inactive'; }?>">							
						<?php previous_post_link('%link', 'next  &rarr;'); ?> 
					</div>	

					<div class="previous_mobile <?php if(!get_previous_post()){ echo 'inactive'; }?>">
						<?php next_post_link('%link', '&larr;'); ?>
					</div>	

					<div class="next_mobile <?php if(!get_next_post()){ echo 'inactive'; }?>">							
						<?php previous_post_link('%link', '&rarr;'); ?> 
					</div>				
				</div> <!-- end navigation -->		
			</div>
			<div <?php post_class("box"); ?>>
				<div class="inside">			
				<?php while (have_posts()) : the_post(); ?>								    
					<div class="clearfix">   						
						<?php the_content(); ?>					
						<?php $project_url = get_post_meta($post->ID, "_ttrust_url_value", true); ?>
						<?php $project_url_label = get_post_meta($post->ID, "_ttrust_url_label_value", true); ?>
						<?php $project_url_label = ($project_url_label!="") ? $project_url_label : __('Visit Site', 'themetrust'); ?>
						<?php if ($project_url) : ?>
							<p><a class="action link" href="<?php echo $project_url; ?>"><?php echo $project_url_label; ?></a></p>
						<?php endif; ?>
						<ul class="skillList clearfix"><?php ttrust_get_terms_list(); ?></ul>	
														
					</div>
					<?php comments_template('', true); ?>	
				<?php endwhile; ?>	

			
				</div>	
			</div>								    	
		</div>
	

<?php get_footer(); ?>

<script type="text/javascript">
	var show_intro = '<?php echo $_SESSION["intro"]; ?>';
</script>