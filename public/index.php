<?php

include ("../document.php");
$document = new Document;
// $response = $document->Run( "2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe" );
$response = $document->Run( "2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe" );
?>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?php print $response["title"]; ?></title>
	<link rel="stylesheet" type="text/css" href="style.css" media="all" />
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
	<?php print $response["styles"]; ?>
	</style>
</head>
<body>
	<div id="content">
		<?php print $response["content"]; ?>
	</div>
</body>