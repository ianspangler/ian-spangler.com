<?php get_header(); ?>		

	<div id="content" class="<?php if ($_SESSION['intro'] == 'Y'): ?>faded_out<?php endif; ?>">

		<div id="pageHead">
			
				<div class="title">
					<h1><?php the_title(); ?></h1>
				</div>

				<div class="line"></div>

				<?php $page_description = get_post_meta($post->ID, "_ttrust_page_description_value", true); ?>
				<?php if ($page_description) : ?>
					<p><?php echo $page_description; ?></p>
				<?php endif; ?>		
				
		</div>

		<div <?php post_class("clearfix box"); ?>>
			
			<div class="inside">
			<?php while (have_posts()) : the_post(); ?>				    					
					<?php the_content(); ?>							
				<?php comments_template('', true); ?>			
			<?php endwhile; ?>
			</div>
		</div>							    	
	</div>		
	
<?php get_footer(); ?>

<script type="text/javascript">
	var show_intro = '<?php echo $_SESSION["intro"]; ?>';
</script>