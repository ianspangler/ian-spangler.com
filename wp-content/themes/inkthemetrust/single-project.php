<?php get_header(); ?>		
		
		<div id="content" class="projectPage clearfix<?php if ($_SESSION['intro'] == 'Y'): ?> faded_out<?php endif; ?>">			
			<div id="pageHead">
				<div class="title"><h1><?php the_title(); ?></h1></div>
				<div class="line"></div>

				<div class="projectNav clearfix">
					<div class="previous <?php if(!get_next_post()){ echo 'inactive'; }?>">
						<?php 

							$link_html = '<i class="fa fa-angle-left" aria-hidden="true"></i> <span>prev</span>';

							if (!get_next_post()) {
								echo '<a href="#">'.$link_html.'</a>';
							}
							else {
								next_post_link('%link', $link_html); 
							}
						?>
					</div>

					<div class="next <?php if(!get_previous_post()){ echo 'inactive'; }?>">							
						<?php 

							$link_html = '<span>next</span> <i class="fa fa-angle-right" aria-hidden="true"></i>';

							if (!get_previous_post()) {
								echo '<a href="#">'.$link_html.'</a>';
							}
							else {
								previous_post_link('%link', $link_html);
							} 

						?>
					</div>	

							
				</div> <!-- end navigation -->		
			</div>
			
			<?php echo get_slideshow(); ?>

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
			<!--<div class="gr-overlay post-gr-overlay"></div>		-->					    	
		</div>
	

<?php get_footer(); ?>

<script type="text/javascript">
	var show_intro = '<?php echo $_SESSION["intro"]; ?>';
</script>