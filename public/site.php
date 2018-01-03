<?php
/*
	Site
	====
	This uses a single Google Document as the basis of content for multiple web pages.

	Original document:
	https://docs.google.com/document/d/1naguPdhgtenA3y_tRtNQU91QlK92zch40YYpa14yoJA/edit

	Publish Link:
	https://docs.google.com/document/d/e/2PACX-1vR-pd40hZJdD073n53Ejt5OMqADdFYDUYj1JJuA1mbuppCqcWCZ3C9WG6xRMpDYXpGo_ZOt0gShfwMK/pub
*/

include ("../document.php");

$document = new Document;
$response = $document->Run( "2PACX-1vR-pd40hZJdD073n53Ejt5OMqADdFYDUYj1JJuA1mbuppCqcWCZ3C9WG6xRMpDYXpGo_ZOt0gShfwMK" );

?>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?php print $response["page"]["title"]; ?></title>
	<link rel="stylesheet" type="text/css" href="css/styles.css" media="all" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	<style>
	html, body {
		font-family: "Arial";
	}
	img {
		width: 100%;
		max-width: 400px;
	}
	<?php print $response["styles"]; ?>
	</style>
</head>
<body>
	<div id="navigation">
		<?php
			foreach ($response["page"]["index"] as $k => $title) {
				if (!empty($title)) {
					print "<a href='?page={$k}'>{$title}</a>\n";
				}
			}
		?>
	</div>
	<div id="content">
		<?php print $response["content"]; ?>
	</div>
</body>