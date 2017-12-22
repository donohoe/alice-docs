<?php

// error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
/*

	Example Documents:

		Page:
		https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub

		Site
		https://docs.google.com/document/d/e/2PACX-1vR-pd40hZJdD073n53Ejt5OMqADdFYDUYj1JJuA1mbuppCqcWCZ3C9WG6xRMpDYXpGo_ZOt0gShfwMK/pub

	ToDo:
		* Site document with a Page that references a different document.
*/
date_default_timezone_set('America/New_York');

$vendorPath = dirname($_SERVER['DOCUMENT_ROOT']) . "/lib";

class Document {

	public function __construct() {

		$this->cacheDir  = "cache";
		$this->cacheTime = 5; /* Minutes */

		$this->pageIndex = array();
		$this->pageCurrent = "";

		$this->pageName  = "";
		$this->pageTitle = "";
		$this->pageMode  = false;
		$this->pageMatch = false;

		if (isset($_GET["page"])) {
			$pageName = $this->sanitziedText($_GET["page"]);
			if (!empty($pageName)) {
				$this->pageName = $pageName;
				$this->pageMode = true;
			}
		}

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
			"status"     => "hit",
			"file"       => "",
			"page"       => array(
				"id"     => "",
				"name"   => $this->pageName,
				"title"  => "",
				"index"  => array(),
				"source" => ""
			),
			"styles"     => "",
			"content"    => "",
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

		if (!empty($this->pageName)) {
			$file = $this->cacheDir . "/" . md5($id) . "_" . $this->pageName . ".js";
		} else {
			$file = $this->cacheDir . "/" . md5($id) . ".js";
		}

		$response["file"] = $file;
		$response["page"]["id"] = $id;
		$response["page"]["source"] = $url;

		$refesh = $_GET["refresh"];

		$fileExists = is_file($file);
		if (!$fileExists || time()-filemtime($file) > $this->cacheTime * 60 || $refesh === "y") {

			$html = $this->getLinkContent($url);
			$status  = "hit";

			if ($html === false) {
				$response["content"] = "<!-- Error -->";
			} else {

				include "../lib/phpQuery.php";

				$doc = phpQuery::newDocument($html);

			/*	Style */
				$response["styles"]    = $this->getStyles($doc);

			/*	Markup */
				$response["content"]   = $this->getMarkup($doc);

			/*	Page Information */
				$response["page"]["index"] = $this->pageIndex;
				$response["page"]["title"] = $this->pageTitle;
		
			/*	Save */
				file_put_contents($file, json_encode($response, JSON_PRETTY_PRINT));
			}

		} else {

			$response = file_get_contents($file);
			$response = json_decode($response, true);

			$response["status"]  = "cache";
		}

		// Helpful for debugging:
		// print "<!--\n\n"; print_r($response); print "\n\n-->\n"; exit;

		return $response;
	}

	private function getMarkup($doc) {

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

					$el = "";

				/*	Check for multi-lines and stop it beyond 2 in a row */

					if ($nl === 1) {
						$el = "<br>";
						$nl = 0;
					}

				/*	Check for a Key */

					$entity = $this->keyEntityManager($t);
					if (!$entity["ignore"]) {
						$el = $entity["markup"];
					} else {

					/*	Check for Tag */

						$h = trim($e->html());
						if (!$this->startsWith($h, ":")) { /* Ignore lines starting with colon */
							$el = "<" . $n . ">" . $h . "</" . $n . ">";
						}
					}

					if ($this->pageMode === true) {
						if ($this->pageMatch === true) {
							$content[] = $el;
						}
					} else {
						$content[] = $el;
					}

				}
			}
		}

		$content = implode("\n\t", $content) . "\n";

		$content = $this->cleanURLs( $content );

		return $content;
	}

/*
	getStyles
	
	We look at the GDoc for style info. There are two places where this happens and we are only interested in one.
	Bear in mind, these rules can change as Google devs make changes...

	The STYLE tag we are intersted has (at various points):
	* Wrapped in an @IMPORT tag
	* Been in a STYLE tag that does not have a style for the BODY tag (or #header, #footer, IFRAME too)

	Right now we look at both conditions to determien what styles to pull in.
*/
	private function getStyles($doc) {

		$response   = "";
		$styleRules = "";
		$styles     = array();

		foreach($doc['style'] as $el){
			$styleContent = trim(pq($el)->text());

		/*	Wrapped in an @import statement */
			$okConditionImport = $this->startsWith($styleContent, "@import");
		/*	Does NOT contain iframe style rule */
			$okConditionRuleMissing = (strrpos($styleContent, "iframe") === false) ? true : false;

			if ($okConditionImport || $okConditionRuleMissing) {
				$rules = explode("}", $styleContent);
				foreach($rules as $rule){
					if ($this->startsWith($rule, ".c")) {
						$styles[] = $rule . "}";
					}
				}
			}
		}

	/*
		A number of styles are superficial and are removed so
		its easier to over-ride with your own styles.
		Adjust this as nesesary.
	*/
		if (!empty($styles)) {
			$response = implode("\n", $styles);
			$response = str_replace(array(
				"font-family:\"Arial\"",
				"vertical-align:baseline",
				"page-break-after:avoid",
				"text-decoration:none",
				"text-align:left",
			), "", $response);
			$response = str_replace(array(";;;", ";;"), ";", $response);
		}

		return $response;
	}

/*
	keyEntityManager

	Check for pre-defniend key/value pairs.
	The most complex cases are determineing that a document deals with multiple pages
	or has a provided title. 
*/
	function keyEntityManager($t) {

		$allowedKeys = array(
			"page",
			"title",
			"image",
			"video",
		);

		$entity = array(
			"ignore" => true,
			"markup" => ""
		);

		if (strpos($t, ":") !== false ) {
			foreach($allowedKeys as $key){
				$key = strtolower($key);

				if ($this->startsWith(strtolower($t), $key . ":")) {

					$entity = array(
						"ignore" => true,
						"markup" => ""
					);

					switch ($key) {
						case "page":

							if ($this->pageMode === true) {
								$pageName = trim(str_replace(array("page:", "Page:"), "", $t));
								if (!empty($pageName)){
									if (!in_array($pageName, $this->pageIndex)) {
										$this->pageIndex[$pageName] = "";
										$this->pageCurrent = $pageName;
									}
									if (strtolower($pageName) === strtolower($this->pageName)) {
										$this->pageMatch = true;
									} else {
										$this->pageMatch = false;
									}
								}
							}
							$entity["ignore"] = false;
							break;

						case "title":

							$title = trim(str_replace(array("title:", "Title:"), "", $t));
							if ($this->pageMode === true) {
								if ($this->pageMatch === true) {
									$this->pageTitle = $title;
								}
								if (isset($this->pageIndex[$this->pageCurrent])) {
									$this->pageIndex[$this->pageCurrent] = $title;
								}
							} else {
								$this->pageTitle = $title;
							}
							$entity["ignore"] = false;
							break;

						case "comment":
							$entity = array(
								"ignore" => false,
								"markup" => "<!-- " . trim(str_replace(array("title:", "Title:"), "", $t)) . " -->"
							);
							break;

						case "css":
						case "image":
						case "video":
						case "javascript":
						default:
							$entity = array(
								"ignore" => false,
								"markup" => $this->addFunctionalElement($t)
							);
							break;
					}
				}
			}
		}

		return $entity;
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

/*
	cleanURLs

	* In Google Doc, all links are prefxied with google.com based URL for tracking. We hates this.
	* Using "mb_convert_encoding" snippet avoids condign issues (default is ISO?)
	* Using "LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD" means that
	  in the output, there will be no DOCTYPE, HTML or BODY tags.

	Rather than reply on $node->setAttribute and then $dom->saveHtml to get the revised HTML
	we use a search and replace as there were problmes with it doing larger improper changes to the HTML.
*/
	private function cleanURLs($html) {
		$hrefPosition = strrpos($html, "href=");
		if (hrefPosition === false) {
			return $html;
		}

		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		$replaceThis = array();
		$withThis    = array();

		foreach ($dom->getElementsByTagName('a') as $node) {
			$href = $node->getAttribute( 'href' );
			$path = str_replace("https://www.google.com/url?q", "q", $href);
			parse_str($path, $output);
			$replaceThis[] = $href;
			$withThis[] = $output["q"];
			$instancesToReplace[] = array($href, $output["q"]);
		}
		unset($dom);

		$html = str_replace($replaceThis, $withThis, $html);

		return $html;
	}

	private function getUserAgentString() {
		return $this->getRandomElements(array(
			"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9",
			"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36",
			"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36",
			"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36",
			"Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36"
		), 1);
	}

	private function getLinkContent($url) {
		$options = array(
			'http' => array(
				'method' => "GET",
				'header' => "Accept-language: en\r\n" .
					"User-Agent: " . $this->getUserAgentString() . "\r\n"
			)
		);
		$context = stream_context_create($options);
		return @file_get_contents($url, false, $context);
	}

/*	Utilities */

	private function getRandomElements( $array, $limit = 1 ) {
		shuffle($array);
		if ( $limit > 0 ) {
			$array = array_splice($array, 0, $limit);
		}
		return $array;
	}

	private function sanitziedText($str) {
		return strtolower( preg_replace('/[^\w-]/', '', $str) );
	}

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

	private function contains($str, array $arr) {
		foreach($arr as $a) {
			if (stripos($str, $a) !== false) return true;
		}
		return false;
	}
}
