<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php bloginfo('name'); ?> <?php wp_title(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale = 1.0, maximum-scale=1.0, user-scalable=no" />
	
	<?php $heading_font = of_get_option('ttrust_heading_font'); ?>
	<?php $body_font = of_get_option('ttrust_body_font'); ?>
	<?php $menu_font = of_get_option('ttrust_menu_font'); ?>
	<?php if ($heading_font != "") : ?>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=<?php echo(urlencode($heading_font)); ?>:regular,italic,bold,bolditalic" />
	<?php else : ?>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Droid+Sans:regular,bold" />
	<?php endif; ?>
	
	<?php if ($body_font != "" && $body_font != $heading_font) : ?>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=<?php echo(urlencode($body_font)); ?>:regular,italic,bold,bolditalic" />
	<?php elseif ($heading_font != "") : ?>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Droid+Sans:regular,bold" />
	<?php endif; ?>
	
	<?php if ($menu_font != "" && $menu_font != $heading_font) : ?>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=<?php echo(urlencode($menu_font)); ?>:regular,italic,bold,bolditalic" />
	<?php elseif ($heading_font != "") : ?>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Droid+Sans:regular,bold" />
	<?php endif; ?>
	
	
	
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="alternate" type="application/atom+xml" title="<?php bloginfo('name'); ?> Atom Feed" href="<?php bloginfo('atom_url'); ?>" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	
	<?php if (of_get_option('ttrust_favicon') ) : ?>
		<link rel="shortcut icon" href="<?php echo of_get_option('ttrust_favicon'); ?>" />
	<?php endif; ?>
	
	<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
	
	<?php wp_head(); ?>	
</head>

<body <?php body_class(); ?> >

<div id="slideNav" class="panel">
	<a href="javascript:jQuery.pageslide.close()" class="closeBtn">&times;</a>					
	
		<?php wp_nav_menu( array('menu_class' => '', 'theme_location' => 'main', 'fallback_cb' => 'default_nav' )); ?>
	
	<?php if(is_active_sidebar('sidebar_slidenav')) : ?>
	<div class="widgets">
		<?php dynamic_sidebar('sidebar_slidenav'); ?>
	</div>
	<?php endif; ?>			
</div>

<div id="container">	
<div id="header">
	<div class="inside clearfix<?php if ($_SESSION['intro'] == 'Y'): ?> hidden<?php endif; ?>">
							
		<?php $ttrust_logo = of_get_option('logo'); ?>
		<div id="logo">
			<?php if($ttrust_logo) : ?>				
				<h1 class="logo"><a href="<?php bloginfo('url'); ?>"><img src="<?php echo $ttrust_logo; ?>" alt="<?php bloginfo('name'); ?>" width="175" height="156" /></a></h1>
			<?php else : ?>				
				<h1><a href="<?php bloginfo('url'); ?>"><?php bloginfo('name'); ?></a></h1>				
			<?php endif; ?>	
			<div id="letters_mask" class="<?php if ($_SESSION['intro'] != 'Y'): ?>hidden<?php endif; ?>">
				<div class="letter_part" id="i_left"></div>
				<div class="letter_part" id="s_bottom"></div>
				<div class="letter_part" id="s_right"></div>
				<div class="letter_part" id="s_middle"></div>
				<div class="letter_part" id="s_left"></div>
				<div class="letter_part" id="s_top"></div>
				<div class="letter_part" id="i_right"></div>
				<div class="letter_part" id="subline"></div>
			</div>
		</div>
		
		<div id="mainNav" class="clearfix<?php if ($_SESSION['intro'] == 'Y'): ?> hidden<?php endif; ?>">							
			<?php wp_nav_menu( array('menu_class' => 'sf-menu', 'theme_location' => 'main', 'fallback_cb' => 'default_nav' )); ?>			
		</div>
		
		<a href="#slideNav" class="menuToggle"></a>				
		
		<div id="sidebar" class="<?php if ($_SESSION['intro'] == 'Y'): ?>hidden<?php endif; ?>">
			<?php get_sidebar(); ?>	
		</div>
	</div>	
</div>

<div id="main" class="clearfix">
