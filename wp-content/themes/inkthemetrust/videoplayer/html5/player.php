<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $_GET['title']; ?></title>
	<style type="text/css">
	
		html, body {
			width: 100%;
			height: 100%;
			margin: 0;
			padding: 0;
			font-family:Helvetica, sans-serif;
			background: #010101;
		}

		.easyhtml5video {
			margin: 0 auto;
		}

	</style>

</head>
<body>
	<!-- ex: 846 x 676 -->
	<?php $path = 'https://s3.amazonaws.com/ianspangler/portfolio/wp-content/videos/eh5v.files/html5video/'; ?>

	<style type="text/css">.easyhtml5video .eh5v_script{display:none}</style>
	<div class="easyhtml5video" style="position:relative;max-width:<?php echo $_GET['width']; ?>px;">
		<video controls="controls"  autoplay="autoplay" poster="<?php echo $path . $_GET['title']; ?>.jpg" style="width:100%" title="<?php echo $_GET['title']; ?>">
			<source src="<?php echo $path . $_GET['title']; ?>.m4v" type="video/mp4" />
			<source src="<?php echo $path . $_GET['title']; ?>.webm" type="video/webm" />
			<object type="application/x-shockwave-flash" data="eh5v.files/html5video/flashfox.swf" width="<?php echo $_GET['width']; ?>" height="<?php echo $_GET['height']; ?>" style="position:relative;">
			<param name="movie" value="<?php echo $path ?>flashfox.swf" />
			<param name="allowFullScreen" value="true" />
			<param name="flashVars" value="autoplay=true&amp;controls=true&amp;fullScreenEnabled=true&amp;posterOnEnd=true&amp;loop=false&amp;poster=<?php echo $path . $_GET['title']; ?>.jpg&amp;src=<?php echo $_GET['title']; ?>.m4v" />
			 <embed src="eh5v.files/html5video/flashfox.swf" width="<?php echo $_GET['width']; ?>" height="<?php echo $_GET['height']; ?>" style="position:relative;"  flashVars="autoplay=true&amp;controls=true&amp;fullScreenEnabled=true&amp;posterOnEnd=true&amp;loop=false&amp;poster=<?php echo $path . $_GET['title']; ?>.jpg&amp;src=<?php echo $_GET['title']; ?>.m4v"	allowFullScreen="true" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer_en" />
			<img alt="<?php echo $_GET['title']; ?>" src="<?php echo $path . $_GET['title']; ?>.jpg" style="position:absolute;left:0;" width="100%" title="Video playback is not supported by your browser" />
			</object>
		</video>
		<div class="eh5v_script"><a href="http://easyhtml5video.com">ogg video</a> by EasyHtml5Video.com v3.5</div>
	</div>
	<!--<script src="eh5v.files/html5video/html5ext.js" type="text/javascript"></script>-->
	
</body>
</html>