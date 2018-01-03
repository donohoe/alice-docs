<?php
/*
	Embeds
	====
	

	Original document:
	https://docs.google.com/document/d/1bWoi9Gmg1LS5hkuYx8ykC_k-JvNeRbyyep456SjPrtE/edit

	Publish Link:
	https://docs.google.com/document/d/e/2PACX-1vSxgb03MiCWgXywb90TyBNuU8jk-jofX0zoshuScDNyPJIEGxQbS8hs-F75vzatiE1ISZd-lLGM1RMj/pub
*/

include ("../document.php");

$document = new Document;
$response = $document->Run( "2PACX-1vSxgb03MiCWgXywb90TyBNuU8jk-jofX0zoshuScDNyPJIEGxQbS8hs-F75vzatiE1ISZd-lLGM1RMj" );

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

	<?php print $response["styles"]; ?>
	</style>
</head>
<body>
	<header>
		<div id="navigation">
			<span>AliceDocs</span>
			<span>
		<?php
			foreach ($response["page"]["index"] as $k => $title) {
				if (!empty($title)) {
					print "<a href='?page={$k}'>{$title}</a>\n";
				}
			}
		?>
			</span>
		</div>
	</header>
	<div id="content">
		<?php print $response["content"]; ?>
	</div>
	<script src="js/main.js"></script>
</body>