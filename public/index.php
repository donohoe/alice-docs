<?php
/*
	Page
	====
	This uses a single Google Document as the basis of content for a web page.
	The Google Doc allows updates every 5 minutes. However the local cache time can be set higher (and should be).

	Original document:
	https://docs.google.com/document/d/1k0-Pg1pqUh31gdSw4QxKSfAFsFsktkSfQbqq2nDUmTw/edit

	Publish Link:
	https://docs.google.com/document/d/e/2PACX-1vQ76OboMhN5zvMZ43LMsu3SvnGts7m8eM3k0VAB5rL22KNjOISNNpN4xCMNyA0dwkf15pxjZ7z1C48i/pub

	https://docs.google.com/document/d/1KKPrL3MCtA0V8K6UIzMzeCdDG54NFDrEDhS5Y6IW6QE/edit
	https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub
*/

include ("../document.php");
$document = new Document;
$response = $document->Run( "2PACX-1vQ76OboMhN5zvMZ43LMsu3SvnGts7m8eM3k0VAB5rL22KNjOISNNpN4xCMNyA0dwkf15pxjZ7z1C48i" );

?>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?php print $response["page"]["title"]; ?></title>
	<link rel="stylesheet" type="text/css" href="css/styles.css" media="all" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<style>
	html, body {
		font-family: "Arial";
	}
	img {
		width: 100%;
		max-width: 400px;
	}
	#content {
		margin-top: 5%;
	}
	<?php print $response["styles"]; ?>
	</style>
</head>
<body>
	<div id="content">
		<?php print $response["content"]; ?>
	</div>
</body>