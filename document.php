<?php

// error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
/*


	Example Documents:
		https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub

	ToDO:
		Links. Google rewrites them liek so:

		From this:
			http://www.google.com/?x=test
		To this:
			https://www.google.com/url?q=http://www.google.com/?x%3Dtest&sa=D&ust=1513804622536000&usg=AFQjCNHFwT9iRKkOJSA_M-xAZixXiGMy3Q
*/
date_default_timezone_set('America/New_York');

$vendorPath = dirname($_SERVER['DOCUMENT_ROOT']) . "/lib";

class Document {

	public $host;

	public function __construct() {
		$this->cacheDir = "cache";
		$this->title = "";

		$this->allowedExtensions = array(
			"js", "css"
		);
		$this->allowedVideoExtensions = array(
			"mp4", "webm", "ogg"
		);
		$this->allowedImageExtensions = array(
			"png", "gif", "jpg", "jpeg"
		);
	}

    function Run($id) {
		$document = $this->getDocument( $id );
		return $document;
	}

	private function getDocument($id) {

		$response = array(
			"status"  => "hit",
			"id"      => "",
			"url"     => "",
			"file"    => "",
			"title"   => "",
			"styles"  => "",
			"content" => "",
		);

		if ($this->startsWith($id, "https")) {
			$url = $id;
			$id = str_replace(array("https://docs.google.com/document/d/e/", "/pub"), "", $id);
		} else {
			$url = "https://docs.google.com/document/d/e/" . $id . "/pub";
		}

		if (!file_exists($this->cacheDir)) {
			mkdir($this->cacheDir, 0777, true);
		}
		$file = $this->cacheDir . "/" . md5($id) . ".js";

		$response["id"]   = $id;
		$response["url"]  = $url;
		$response["file"] = $file;

		$refesh = $_GET["refresh"];

		$fileExists = is_file($file);
		if (true || !$fileExists || time()-filemtime($file) > 1 * 60 || $refesh === "y") { /* 1 minute */

			include "../lib/phpQuery.php";
			$status  = "hit";
			$options = array(
				'http' => array(
					'method' => "GET",
					'header' => "Accept-language: en\r\n" .
						"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36\r\n" // i.e. An iPad
				)
			);

			$context = stream_context_create($options);
			$html    = @file_get_contents($url, false, $context);

			if ($html === false) {
				$response["content"] = "<!-- Error -->";
			} else {

				$doc = phpQuery::newDocument($html);

			/*	Style */
				$response["styles"]  = $this->getStyles($doc);

			/*	Markup */
				$response["content"] = $this->getMarkup($doc);

			/*	Title */
				$response["title"]   = $this->title;

			/*	Save */
				file_put_contents($file, json_encode($response, JSON_PRETTY_PRINT));
			}

		} else {
			$response = json_decode($file, true);
			$response["status"]  = "cache";
		}

		return $response;
	}

	private function getMarkup($doc) {

		/* TODO: Handle links and auto-links */

		$allowedTags = array(
			"p",
			"img",
			"table",
			"ul", "ol",
			"h1", "h2", "h3", "h4", "h5", "h6",
			"hr"
		);

		$content = array();

		foreach($doc['#contents > *'] as $el){

			$n = $el->nodeName;
			$nl = 0;

		/*	Tags */
			if (in_array($n, $allowedTags)) {
				$e = pq($el);
				$t = trim($e->text());

				$noImage = strpos($t, "<img ");

				if ($t === "" && $noImage) {
					$nl = 1;
				} else {

				/*	Check for multi-lines and stop it beyond 2 in a row */

					if ($nl === 1) {
						$content[] = "<br>";
						$nl = 0;
					}

				/*	Check for a Key */

					$keyEntity = $this->keyEntityManager($t);
					if (!empty($keyEntity)) {
						$content[] = $keyEntity;
						continue;
					}

				/*	Check for Tag */

					$h = trim($e->html());
					if (!$this->startsWith($h, ":")) { /* Ignore lines starting with colon */
						$content[] = "<" . $n . ">" . $h . "</" . $n . ">";
					}
				}

			}
		}

		$content = implode("\n", $content);

		$content = $this->cleanURLs( $content );

		return $content;
	}

	private function getStyles($doc) {

		$response   = "";
		$styleRules = "";
		$styles     = array();

		foreach($doc['style'] as $el){
			$styleContent = trim(pq($el)->text());
			if ($this->startsWith($styleContent, "@import")) {
				$rules = explode("}", $styleContent);
				foreach($rules as $rule){
					if ($this->startsWith($rule, ".c")) {
						
						$styles[] = $rule . "}";
					}
				}
			}
		}

		if (!empty($styles)) {
			$response = implode("\n", $styles);

			$response = str_replace(array(
				"font-family:\"Arial\"",
				"vertical-align:baseline",
				"page-break-after:avoid",
				"text-decoration:none",
				"text-align:left",
				// "font-style:normal",
				// "font-weight:400",
			), "", $response);
			$response = str_replace(array(";;;", ";;"), ";", $response);
		}

		return $response;
	}

	function keyEntityManager($t) {

		$allowedKeys = array(
			"title",
			"image",
			"video",
		);

		$markup = "";

		if (strpos($t, ":") !== false ) {
			foreach($allowedKeys as $key){
				$key = strtolower($key);
				if ($this->startsWith(strtolower($t), $key  . ":")) {
					switch ($key) {
						case "title":
							$this->title = trim(str_replace(array("title:", "Title:"), "", $t));
							break;
						case "comment":
							$markup = "<!-- " . trim(str_replace(array("title:", "Title:"), "", $t)) . " -->";
							break;
						case "css":
						case "image":
						case "video":
						case "javascript":
						default:
							$markup = $this->addFunctionalElement($t);
							break;
					}
				}
			}
		}

		return $markup;
	}

	private function addFunctionalElement($t) {
		$parts = explode(":", $t, 2);
		if (isset($parts[1])) {
			$url = trim($parts[1]);

			if ($this->startsWith($url, "http") || $this->startsWith($url, "/")) {

				$ext = $this->getFileExtension($url);

			/*	Check Images */
				if (in_array($ext, $this->allowedImageExtensions)) {
					return "<img src=\"" . $url  . "\"/>";
				}

			/*	Check Video */
				if (in_array($ext, $this->allowedVideoExtensions)) {
					return "<video controls><source src=\"" . $url  . "\" type=\"video/" . $ext . "\"></video>";
				}

			/*	Check JS and CSS */
				if (in_array($ext, $this->allowedExtensions)) {
					$h = "";
					if ($extension === "js") {
						$h = "<script src=\"" . $url  . "\"></script>";
					}
					if ($extension === "css") {
						$h = "<link href=\"" . $url  . "\" media=\"all\" rel=\"stylesheet\"/>";
					}
					return $h;
				}
			}
		}
		return "";
	}

	private function cleanURLs($html) {
		$hrefPosition = strrpos($html, "href=");
		if (hrefPosition === false) {
			return $html;
		}

		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
		libxml_clear_errors();

		foreach ($dom->getElementsByTagName('a') as $node) {
			$href = $node->getAttribute( 'href' );
			$path = str_replace("https://www.google.com/url?q", "q", $href);
			parse_str($path, $output);
			$node->setAttribute('href', $output["q"]);
		}	

		$html = $dom->saveHtml();
		unset($dom);
		return $html;
	}

/*	Utilities */

	private function getFileExtension($url) {
		return strtolower( end( explode(".", parse_url($url, PHP_URL_PATH)) ) );
	}

/*	http://stackoverflow.com/a/17852480/24224 */
	private function truncate($str, $width) {
		return strtok(wordwrap($str, $width, "&hellip;\n"), "\n");
	}

	private function startsWith($haystack, $needle) {
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	private function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
}
