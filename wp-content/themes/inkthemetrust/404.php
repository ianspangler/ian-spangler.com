<?php get_header(); ?>	
							 
		<div id="content">
			<div class="page clearfix">
				<div id="pageHead">
					<div class="title">
						<h1><?php _e('Page Not Found', 'themetrust'); ?></h1>
						<div class="line"></div>
					</div>
				</div>
				<div class="clearfix box">
					<div class="inside">
						<p><?php _e("Sorry, but what you're looking for could not be found.", 'themetrust'); ?></p> 
					</div>
				</div>
			</div>
		</div>
			
<?php get_footer(); ?>

<script type="text/javascript">
	
	var show_intro = '<?php echo $_SESSION["intro"]; ?>';

</script>