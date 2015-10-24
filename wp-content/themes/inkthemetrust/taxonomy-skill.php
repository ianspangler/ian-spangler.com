<?php get_header(); ?>
<!--
<?php $term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' )); 
	$parts = explode(": ", $term->name);
	$term->name = $parts[1];
?>
-->
<div id="content" class="fullProjects clearfix full">
	<!--<?php get_template_part( 'part-loading'); ?>-->
	<!--<div id="pageHead">
		<h1><?php echo $term->name; ?></h1>			
	</div>-->
	<?php get_template_part( 'part-projects'); ?>
	<!--
	<div id="projects">		
		<div class="thumbs masonry">
		<?php query_posts( 'skill='.$term->slug.'&post_type=project&posts_per_page=200' ); ?>			
		<?php  while (have_posts()) : the_post(); ?>		
			<?php get_template_part( 'part-project-thumb'); ?>	
		<?php endwhile; ?>
		</div>	
	</div>
	-->
</div>

<?php get_footer(); ?>
