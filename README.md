# VeronicaDocs

A simplified framework and structure for using Google Docs editor to write, mange, and publish content to the web in a quick and friendly manner with minimal setup and configuration.

## Sandbox & Local Development
```
mkdir ~/project
cd  ~/project
git clone git@github.com:donohoe/veronica-docs.git
cd veronica-docs/public
php -S localhost:2340
```

Browser:
http://localhost:2340/

## Linking a Google Document:

1. Create a new Google Document by visiting this link:
https://docs.google.com/document/create
2. Copy/Paste this example into the document:
```
Hello World
Title: Hello
This is simple single page example. It really doesn't get more basic than this.
Google only allows updates to go through every 5 minutes so this isn't going to be any good for live-blogging. In addition, the code has local file caching set to 5 minutes so it could be anywhere between 5 and 10 minutes for changes to take affect.
```
3. Go to: `File > Publish` to the web...
4. Click `Publish`
5. When asked to confirm, choose `OK`
6. Copy the URL that appears in as the new link
7. Click the "X" in top-right corner to close.

There are a number of Google Documents already Published to the web that demonstrates styles and functioanlity. They include:

1. Hello World
  * Very basic example.
  * [Document URL](https://docs.google.com/document/d/1k0-Pg1pqUh31gdSw4QxKSfAFsFsktkSfQbqq2nDUmTw/)
  * [Web Publish URL](https://docs.google.com/document/d/e/2PACX-1vQ76OboMhN5zvMZ43LMsu3SvnGts7m8eM3k0VAB5rL22KNjOISNNpN4xCMNyA0dwkf15pxjZ7z1C48i/pub)
    * Google ID is: _2PACX-1vQ76OboMhN5zvMZ43LMsu3SvnGts7m8eM3k0VAB5rL22KNjOISNNpN4xCMNyA0dwkf15pxjZ7z1C48i_
2. Styles and Elements
  * Various supported text styling, fonts, headers and usag eof tables, images, css etc.
  * [Document URL](https://docs.google.com/document/d/1KKPrL3MCtA0V8K6UIzMzeCdDG54NFDrEDhS5Y6IW6QE/)
  * [Web Publish URL](https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub)
    * Google ID is: _2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe_
3. Pages
  * An example of using one document to manage 3 web pages
  * [Document URL](https://docs.google.com/document/d/1naguPdhgtenA3y_tRtNQU91QlK92zch40YYpa14yoJA/)
  * [Web Publish URL](https://docs.google.com/document/d/e/2PACX-1vR-pd40hZJdD073n53Ejt5OMqADdFYDUYj1JJuA1mbuppCqcWCZ3C9WG6xRMpDYXpGo_ZOt0gShfwMK/pub)
    * Google ID is: _2PACX-1vR-pd40hZJdD073n53Ejt5OMqADdFYDUYj1JJuA1mbuppCqcWCZ3C9WG6xRMpDYXpGo_ZOt0gShfwMK_


## To Do

* Question: Remove phpQuery as a dependency?
* More documentation on usage (multi-page etc)
* Embeds
  * Embed external media and elements.
  * Example: Twitter, Instagram, Google Forms, YouTube etc.
* Syntax for indicating group of images to be treated as a Slideshow
* Quote or escape things so I can show code snippets

## Dependencies

* [phpQuery](https://github.com/punkave/phpQuery)
  * [Documentation](https://code.google.com/archive/p/phpquery/wikis/Manual.wiki)
  * Example: [Manipulating DOM Documents with phpQuery](https://codingexplained.com/coding/php/manipulating-dom-documents-with-phpquery)
