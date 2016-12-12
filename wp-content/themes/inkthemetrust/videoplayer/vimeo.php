<!doctype html>
<html lang="en">
<head>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style type="text/css">

		html, body {
			width: 100%;
			height: 100%;
			margin: 0;
			padding: 0;
			font-family:Helvetica, sans-serif;
			background: #010101;
		}


	</style>

</head>
<body>
	<div class="container">
		<iframe  src="<?php echo $_GET['video_url']; ?>?byline=0&portrait=0&autoplay=1" width="<?php echo $_GET['width']; ?>" height="<?php echo $_GET['height']; ?>" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
	</div>
</body>
</html>