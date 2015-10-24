	<div id="footer" class="<?php if ($_SESSION['intro'] == 'Y'): ?>hidden<?php endif; ?>">					
		<!--<?php $footer_text = of_get_option('ttrust_footer_text'); ?>			
		<div class="inside"><p><?php if($footer_text){echo($footer_text);} else{ ?>Theme by <a href="http://themetrust.com" title="Premium WordPress Themes"><strong>Theme Trust</strong></a><?php }; ?></p></div>-->
	
		<div class="inside">
			<div class="contact">
				
				<a href="mailto:ian@ian-spangler.com"><i class="fa fa-envelope"></i></a>
				<a href="http://linkedin.com/in/ianspangler" target="_blank"><i class="fa fa-linkedin"></i></a>
				<a href="http://twitter.com/ianspangler" target="_blank"><i class="fa fa-twitter"></i></a>

			</div>	
		</div>
	</div><!-- end footer -->
</div><!-- end main -->
</div><!-- end container -->
<?php wp_footer(); ?>
</body>
</html>