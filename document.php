<?php
/*

	Example Documents:

		Page:
		https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub

		Site
		https://docs.google.com/document/d/e/2PACX-1vR-pd40hZJdD073n53Ejt5OMqADdFYDUYj1JJuA1mbuppCqcWCZ3C9WG6xRMpDYXpGo_ZOt0gShfwMK/pub

	ToDo:
		* Site document with a Page that references a different document.

	More information:
	* https://github.com/donohoe/veronica-docs

*/
date_default_timezone_set('America/New_York');

class Document {

	public function __construct() {

		$this->path      = dirname($_SERVER['DOCUMENT_ROOT']);
		$this->cacheDir  = $this->path . "/public/cache";
		$this->cacheTime = 5; /* Minutes */
		$this->setupDirs();

		$this->embedLazyLoad = false;

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

		$this->embedRegex = implode("|", array(
				"(https?:\/\/w{0,3}\.?instagram.com\/p\/[\w\d\-]{10,15}\/?)",
				"(https?:\/\/w{0,3}\.?twitter.com\/\w*\/status\/\d{4,20}\/?)[\'\"]{1}>\w*\s*\d{1,2},\s*\d{4}",
				"(https?:\/\/w{0,3}\.?(?:youtube\.com|youtu\.be)\/(?:watch|v|vi|embed)?\/?(?:\?v=|\?vi=)?[\w\d\-]{10,15}\/?(?:\?.*)?)",
				"(https?:\/\/w\.soundcloud.com\/player\/\?url=https%3A\/\/api\.soundcloud\.com\/.*\")",
				"(https?:\/\/w{0,3}\.?soundcloud.com\/[\w\d\-\_]*\/[\w\d\-]*\/?)",
				"(https?:\/\/embed.spotify.com\/[=\dA-Za-z?%]*)\"",
				"(https?:\/\/w{0,3}\.facebook\.com\/plugins\/post\.php\?href=https?[\w\d%&;=\.]*)\"",
				"(\/\/connect\.facebook\.net\/en_US\/sdk\.js#[\d\w=&.]*)",
				"(https?:\/\/w{0,3}.google.[a-z.]+\/maps\/(?:.*?))\""
			)
		);
	}

    function Run($id) {
		$document = $this->getDocument( $id );
		return $document;
	}

	private function setupDirs() {
		if (!file_exists($this->cacheDir)) {
			mkdir($this->cacheDir, 0777, true);
			mkdir($this->cacheDir . "/embeds", 0777, true);
		}
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
		if (true || !$fileExists || time()-filemtime($file) > $this->cacheTime * 60 || $refesh === "y") {

			$html = $this->getLinkContent($url);
			$status  = "hit";

			if ($html === false) {
				$response["content"] = "<!-- Error -->";
			} else {

				include $this->path . "/lib/phpQuery.php";

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

				if (empty($t) && $n !== "hr" && $noImage) {
					continue;
				}
				$el = "";

			/*	Check for multi-lines and stop it beyond 2 in a row */
				if ($nl === 1) {
					$content[] = "<br>";
					$nl = 0;
					continue;
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

		if (!empty($content)) {
			$content = implode("\n\t", $content) . "\n";
			$content = $this->cleanURLs( $content );
		} else {
			$content = "";
		}

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
				"font-size:11pt",
				"line-height:1.15", /* LI etc */
				"margin-left:36pt"  /* LI etc */
			), "", $response);
			$response = str_replace(
				array(
					"font-family:\"Consolas\"",
				), 
				array(
					"font-family:monospace; letter-spacing:-0.6px; word-spacing:-3px;",
				),
				$response);
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
			"quote",
			"escape",
			"page",
			"title",
			"embed",
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

						case "quote":
						case "escape":
							$entity = $this->keyQuote($t);
							break;

						case "page":
							$entity = $this->keyPage($t);
							break;

						case "title":
							$entity = $this->keyTitle($t);
							break;

						case "comment":
							$entity = array(
								"ignore" => false,
								"markup" => "<!-- " . trim(str_replace(array("title:", "Title:"), "", $t)) . " -->"
							);
							break;

						case "embed":
							$entity = array(
								"ignore" => false,
								"markup" => $this->keyEmbed($t)
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

	private function keyQuote($text) {
		$text = trim(str_replace(array("quote:", "Quote:", "escape:", "Escape:"), "", $text));
		if (!empty($text)) {
			$text = "<p class=\"quote\">" . $text . "</p>";
		}
		return $entity = array(
			"ignore" => false,
			"markup" => $text
		);
	}

	private function keyPage($text) {
		if ($this->pageMode === true) {
			$pageName = trim(str_replace(array("page:", "Page:"), "", $text));
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

		return $entity = array(
			"ignore" => false,
			"markup" => ""
		);
	}

	private function keyTitle($text) {
		$title = trim(str_replace(array("title:", "Title:"), "", $text));
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

		return $entity = array(
			"ignore" => false,
			"markup" => ""
		);
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

	private function keyEmbed($text) {
		$html = "";
		$parts = explode(":", $text, 2);
		if (isset($parts[1])) {
			$url = trim($parts[1]);

		/*	All external embeds must start with HTTP. Ideally HTTPS but lets not be picky just yet */
			if ($this->startsWith($url, "http")) {

				$host = str_replace("www.", "", parse_url($url, PHP_URL_HOST));
				switch ($host) {
					case "documentcloud.org":
						$html = $this->embedDocumentCloud($url);
						break;

				/*	Video and Moving Image */
					case "youtube.com":
					case "youtu.be":
						$html = $this->embedGeneric(
							$url,
							"#([\/|\?|&]vi?[\/|=]|youtu\.be\/|embed\/)(\w+)#",
							"https://www.youtube.com/embed/",
							"youtube"
						);
						break;
					case "vimeo.com":
						$html = $this->embedGeneric(
							$url,
							"/vimeo.com\/([\w\d]*)/",
							"https://player.vimeo.com/video/",
							"vimeo"
						);
						break;
					case "giphy.com":
						$html = $this->embedGeneric(
							$url,
							"/giphy.com\/embed\/([\w\d]*)/",
							"https://giphy.com/embed/",
							"giphy"
						);
						break;

				/*	Social */

					case "twitter.com":
						$html = $this->embedTwitter($url);
						break;
					case "instagram.com":
						$html = $this->embedInstagram($url);
						break;
					case "facebook.com":
					case "connect.facebook.net":
						$html = $this->embedFacebook($url, $content);
						break;

					case "cdn.playbuzz.com":
						$html = $this->embedPlaybuzz($content);
						break;
					case "google.com":
						$html = $this->embedGoogleMap($url);
						break;

				/*	Audio */

					case "soundcloud.com":
					case "w.soundcloud.com":
						$html = $this->embedSoundCloud($url);
						break;
					case "spotify.com":
					case "embed.spotify.com":
						$html = $this->embedSpotify($url);
						break;

					default:
						if (strpos($content, "</iframe>") > 0) {
							$embedHTML = $this->parseIframe($content);
						} else {
							$embedHTML = "<!-- embed not recognized -->";
						}
				}

			}
		}
		return $html;
	}

/*	Misc */

	/*
	DocumetCloud
	*/
	private function embedDocumentCloud($srcLink) {
		$url = str_replace("http://", "https://", $srcLink);
		$dataUrl = "data-url=$url";
		$scriptLink = "//assets.documentcloud.org/viewer/loader.js";
		$sectionContent = "<a href=$url target=\"_blank\">$url</a>";
		$html = $this->embedBasicScript($scriptLink, "documentcloud", $sectionContent, "", $dataUrl);
		return $html;
	}

/*	Video */

	/*
	YouTube
	Docs
		https://developers.google.com/youtube/player_parameters
		http://stackoverflow.com/a/26660288/24224
	Example URLs:
		https://www.youtube.com/watch?v=y2bX2UkQpRI
		https://youtube.com/v/y2bX2UkQpRI
		https://youtube.com/vi/y2bX2UkQpRI
		https://youtube.com/?v=y2bX2UkQpRI
		https://youtube.com/?vi=y2bX2UkQpRI
		https://youtube.com/watch?v=y2bX2UkQpRI
		https://youtube.com/watch?vi=y2bX2UkQpRI
		https://youtu.be/y2bX2UkQpRI
		https://http://youtu.be/y2bX2UkQpRI?t=30m26s
		https://youtube.com/embed/y2bX2UkQpRI
	*/

	/*
	Vimeo
	Docs

	Example URL:
	https://vimeo.com/244506823
		<iframe src="https://player.vimeo.com/video/244506823" 
			width="640" height="360" frameborder="0" 
			webkitallowfullscreen mozallowfullscreen allowfullscreen>
		</iframe>
	*/
	private function embedGeneric($link, $embedRegex, $embedPath, $name, $klasses = "") {
		preg_match($embedRegex, $link, $matches);
		$embedId = end($matches);
		$link = $embedPath . $embedId;
		$html = $this->embedBasicIframe($link, $name, "video embed-". $name . " " . $klasses);
		return $html;
	}

/*	Social */

	/*
	Twitter
	Docs:
		https://dev.twitter.com/web/embedded-tweets/cms
	Example URL:
		https://twitter.com/sfchronicle/status/841544173631205376
	*/
	private function embedTwitter($srcLink) {
		$html = $srcLink;
		if (strpos($srcLink, "status") > 0) {
			$url = str_replace("http://", "https://", $srcLink);

			$dataUrl = "data-url=$url";
			$dataUrl = "url=$url";
			$scriptLink = "https://platform.twitter.com/widgets.js";
			$sectionContent = implode("\n", array(
				"<blockquote class=\"twitter-tweet\">",
					"<a href=\"{$url}\">{$url}</a>",
				"</blockquote>"
			));

			if ($this->embedLazyLoad === false) {
				$sectionContent .= "<script src=\"{$scriptLink}\"></script>";
			}
			/* provide a custom class to avoid applying fallback 4:3 aspect ratio */
			$html = $this->embedBasicScript($scriptLink, "twitter", $sectionContent, "media-twitter", $dataUrl);
		}
		return $html;
	}


	/*
	Instagram
	Docs:
	https://www.instagram.com/developer/embedding/
	Example URL:
	https://www.instagram.com/p/BNicURWD7Vj/
	*/
	private function embedInstagram($srcLink) {
		$html = $srcLink;
		if (strpos($srcLink, "instagram.com") > 0) {
			$url = str_replace("http://", "https://", $srcLink);
			$dataUrl = "data-url=$url";
			$scriptLink = "//platform.instagram.com/en_US/embeds.js";
			$sectionContent = implode("\n", array(
				"<blockquote class=\"instagram-media\" data-instgrm-captioned data-instgrm-version=7>",
					"<a href=$url>$url</a>",
				"</blockquote>"
			));
			if ($this->embedLazyLoad === false) {
				$sectionContent .= "<script src=\"{$scriptLink}\"></script>";
			}
			$html = $this->embedBasicScript($scriptLink, "instagram", $sectionContent, "media-instagram", $dataUrl);
		}
		return $html;
	}


	/*
	SoundCloud
	Works with embed URLs and regular SoundCloud links.
	Docs
		https://soundcloud.com/pages/embed
	Example URL:
		https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/34019569&amp;color=0066cc
	*/
	private function embedSoundCloud($srcLink) {
		$url = str_replace("http://", "https://", $srcLink);
		if (strpos($url, "w.soundcloud") === false && strpos($url, "api.soundcloud") === false) {
			$url = implode("\n", array(
				"https://w.soundcloud.com/player/?",
				"url={$url}&",
				"auto_play=false&",
				"buying=true&liking=false&",
				"download=true&",
				"sharing=true&",
				"show_artwork=true&",
				"show_comments=false&",
				"show_playcount=false&",
				"show_user=true&",
				"hide_related=false&",
				"visual=false&",
				"start_track=0&",
				"callback=true"
			));
		}
		$html = $this->embedBasicIframe($url, "soundcloud", "audio media-soundcloud");
		return $html;
	}

	/*
	Facebook
	Works with iframe and script embeds
	Examples:
		https://www.facebook.com/plugins/post.php?
			href=https%3A%2F%2Fwww.facebook.com%2FcandaceSpayne%2Fvideos%2F10209653193067040%2F&amp;
			width=500&amp;
			show_text=true&amp;
			appId=254809167894401&amp;
			height=634
	*/
	private function embedFacebook($url, $content)
	{
		if (strpos($url, "video") > 0) {
			// Video embed
			$url = preg_replace("/width=[\'\"]?\d{2,4}[\'\"]?/", "width=300", $url);
			$url = preg_replace("/height=[\'\"]?\d{2,4}[\'\"]?/", "height=168", $url);
			$html = $this->embedBasicIframe($url, "facebook-iframe", "embed-facebook-video aspect-16-9");
		} else {
			if (strpos($url, "plugins") > 0) {
				// Iframe embed
				$url = preg_replace("/width=[\'\"]?\d{2,4}[\'\"]?/", "width=300", $url);
				$url = preg_replace("/height=[\'\"]?\d{2,4}[\'\"]?/", "height=380", $url);
				$html = $this->embedBasicIframe($url, "facebook-iframe", "embed-facebook");
			} else {
				// Script embed
				preg_match("/(\/\/connect\.facebook\.net\/en_US\/sdk\.js#[\d\w=&.]*)/", $content, $matches);
				$scriptLink = end($matches);
				$sectionContent = substr($content, 0, strpos($content, "<script>")) . substr($content,
						strpos($content, "</script>") + 9);
				$html = $this->embedBasicScript($scriptLink, "facebook", $sectionContent, "media-facebook");
			}
		}

		return $html;
	}

	/*
	PlayBuzz
	Docs:
		https://publishers.playbuzz.com/academy/how_to/how-do-i-embed/
	Example URL:
		http://www.playbuzz.com/item/b6b22c71-46da-48d6-8005-83ab4e1f8830
	*/
	private function embedPlaybuzz($content) {
		$div = trim(strip_tags($content, "<div>"));
		$scriptLink = "//cdn.playbuzz.com/widget/feed.js";
		$html = $this->embedBasicScript($scriptLink, "playbuzz", $div, "");
		return $html;
	}

	/*
	Spotify
	Works with embed URLs
	Docs
		https://soundcloud.com/pages/embed
	Example URL:
		https://embed.spotify.com/?uri=spotify%3Auser%3Asfchronicle%3Aplaylist%3A4zxPdQDo2VKvl6GFc7dBDF
	*/
	private function embedSpotify($srcLink) {
		$html = $this->embedBasicIframe($srcLink, "spotify", "audio media-spotify");
		return $html;
	}

	/*
	Google Maps
	Docs:
	https://developers.google.com/maps/documentation/embed/guide
	Example URL:
	https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3153.336280669685!2d-122.4087719!3d37.7821582!3m2!1i1024!2i768!4f13.1!3m3!1m2!!2sSan+Francisco+Chronicle!
	*/
	private function embedGoogleMap($srcLink) {
		$html = $srcLink;
		if (strpos($srcLink, "google.com/maps") > 0) {
			$html = $this->embedBasicIframe($srcLink, "google-maps", "embed-aspect-1-1");
		}
		return $html;
	}


	/*
	 Basic Iframe Embed
	 */
	private function embedBasicIframe($srcLink, $dataComponent = false, $customClasses = "", $customAttrbs = "") {
		$url = str_replace("http://", "https://", $srcLink);
		$dataComponent = !$dataComponent ? "misc-iframe" : $dataComponent;
		if (is_array($customClasses)) {
			$customClasses = implode(" ", $customClasses);
		}

		// $html = implode("\n", array(
		// 	"<section class=\"embed " . $customClasses . "\">",
		// 	"<iframe data-progressive=\"true\" data-component=$dataComponent data-url=$url " . $customAttrbs .
		// 	" frameborder=\"0\" scrolling=\"0\" allowfullscreen webkitallowfullscreen mozallowfullscreen msallowfullscreen>",
		// 	"</iframe>",
		// 	"</section>"
		// ));

		$html = implode("\n", array(
			"<section class=\"embed " . $customClasses . "\">",
			"<iframe data-progressive=\"true\" data-component=$dataComponent src=$url " . $customAttrbs .
			" frameborder=\"0\" scrolling=\"0\" allowfullscreen webkitallowfullscreen mozallowfullscreen msallowfullscreen>",
			"</iframe>",
			"</section>"
		));

		return $html;
	}

	/*
	 Basic Script Embed
	 */
	private function embedBasicScript(
		$srcLink,
		$dataComponent = false,
		$customContent = "",
		$customClasses = "",
		$customAttrbs = ""
	) {
		$url = str_replace("http://", "https://", $srcLink);
		$dataComponent = !$dataComponent ? "misc-embed-script" : $dataComponent;
		if (is_array($customClasses)) {
			$customClasses = implode(" ", $customClasses);
		}
		$html = implode("\n", array(
			"<section class=\"embed " . $customClasses . "\" data-progressive=\"true\"" .
			" data-component=$dataComponent data-js=$url " . $customAttrbs . ">",
			$customContent,
			"</section>"
		));

		return $html;
	}




/*
	cleanURLs

	* In Google Doc, all links are prefxied with google.com based URL for tracking. We hates this.
	* Using "mb_convert_encoding" snippet avoids condign issues (default is ISO?)
	* Using "LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD" means that
	  in the output, there will be no DOCTYPE, HTML or BODY tags.

	Rather than reply on $node->setAttribute and then $dom->saveHtml to get the revised HTML
	we use a search and replace as there were problmes with it doing larger improper changes to the HTML.

	We do't want his to run unesesariyly if:
	* there is no link
	* embedded media
	
	TODO: this handles media blocks badly :(
*/
	private function cleanURLs($html) {
		$hrefPosition = strrpos($html, "href=");
		if (hrefPosition === false) {
			return $html;
		}

		$isEmbed = strrpos($html, "embed");
		if (isEmbed !== false) {
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
