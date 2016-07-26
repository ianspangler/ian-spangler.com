

<?php $term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' )); 
	$parts = explode(": ", $term->name);
	$term->name = $parts[1];
?>

<div id="projects" class="clearfix">		
	<?php get_template_part( 'part-loading'); ?>
	<?php $page_skills = get_post_meta($post->ID, "_ttrust_page_skills_value", true); ?>
	
	<?php if ($page_skills) : // if there are a limited number of skills set ?>
		<?php $skill_slugs = ""; $skills = explode(",", $page_skills); ?>

		<?php if (sizeof($skills) > 1) : // if there is more than one skill, show the filter nav?>	

			<ul id="filterNav" class="clearfix">

				<li class="allBtn"><a href="#" data-filter="*" class="selected"><?php _e('All', 'themetrust'); ?></a></li>

				<?php
				$j=1;	
				 
				foreach ($skills as $skill) {				
					$skill = get_term_by( 'slug', trim(htmlentities($skill)), 'skill');
					if($skill) {
						$skill_slug = $skill->slug;				

						$skill_slugs .= $skill_slug . ",";
		  				$a = '<li><a href="#" data-filter=".'.$skill_slug.'">';
						$a .= $skill->name;					
						$a .= '</a></li>';
						echo $a;
						echo "\n";
						$j++;
					}		  
				}
				
				?>
			</ul>
			<?php $skill_slugs = substr($skill_slugs, 0, strlen($skill_slugs)-1); ?>
		<?php else: ?>
			
			<?php $skill = $skills[0]; ?>
			<?php $s = get_term_by( 'slug', trim(htmlentities($skill)), 'skill'); ?>
			<?php if($s) { $skill_slugs = $s->slug; } ?>
		<?php endif; 	

		query_posts( 'skill='.$skill_slugs.'&post_type=project&posts_per_page=200' );

	else : // if not, use all the skills ?>
		<div class="filter_wrap<?php if ($_SESSION['intro'] == 'Y'): ?> faded_out<?php endif; ?>">
			<div class="fade"></div>
			<?php
				
				$skills = get_terms('skill');
				array_walk($skills, 'trim_indices');

				//if ( wp_is_mobile() ) {
				//} else {
					echo buildSkillsMenu($skills, "nav"); //hidden on mobile
					echo buildSkillsMenu($skills, "select"); //hidden on desktop
				//}
			?>
		</div>
		<?php query_posts( 'post_type=project&posts_per_page=200' );

	endif; ?>
	
	<div class="thumbs masonry">			
	<?php  while (have_posts()) : the_post(); ?>
		
		<?php
		global $p;				
		$p = "";
		$skills = get_the_terms( $post->ID, 'skill');
		if ($skills) {
		   foreach ($skills as $skill) {				
		      $p .= $skill->slug . " ";						
		   }
		}
		?>  	
		<?php get_template_part( 'part-project-thumb'); ?>		

	<?php endwhile; ?>

	<?php
		function buildSkillsMenu($skills, $type = "nav") {

			$nav_width = 100/(count($skills)+1);
			
			if ($type == "nav") { $wrap_div = "ul"; $item_div = 'li style="width:'.$nav_width.'%"'; }
			else if ($type == "select") { $wrap_div = "select"; $item_div = "option"; }

			$menu = '<'.$wrap_div.' id="filterNav" class="clearfix">';

			$menu .= '<'.$item_div.' class="allBtn" data-filter="*"><a href="#" data-filter="*" class="selected">All Projects</a></'.$item_div.'>';
		
			$j=1;
			
			
			foreach ($skills as $skill) {
				$a = '<'.$item_div.' data-filter=".'.$skill->slug.'"><a href="#" data-filter=".'.$skill->slug.'">';
		    	$a .= $skill->name;					
				$a .= '</a></'.$item_div.'>';
				$menu .= $a;
				$menu .= "\n";
				$j++;
			}
				
			$menu .= '</'.$wrap_div.'>';

			return $menu;
		}
	?>
	</div>
	<?php wp_reset_query();?>
</div>

<script type="text/javascript">
	var selected_skill_name = '<?php echo $term->name; ?>'; 
	var selected_skill_slug = '<?php echo $term->slug; ?>'; 
	var show_intro = '<?php echo $_SESSION["intro"]; ?>';
</script>

