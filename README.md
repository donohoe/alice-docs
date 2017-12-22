# VeronicaDocs

A simplified framework and structure for using Google Docs to edit, mange, and publish content to the web in a quick and friendly manner with minimal setup and configuration.

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
headline: This is it
leadin: Hello to the lead in.
kicker: blah
```
3. Go to: `File > Publish` to the web...
4. Click `Publish`
5. When asked to confirm, choose `OK`
6. Copy the URL that appears in as the new link
7. Click the "X" in top-right corner to close.

There is a Google Document already Published to the web that demonstrates styles and formats the come through:

https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub

## To Do

* Question: Remove phpQuery as a dependency?
* More documentation on usage (multi-page etc)

## Dependencies

* [phpQuery](https://github.com/punkave/phpQuery)
  * [Documentation](https://code.google.com/archive/p/phpquery/wikis/Manual.wiki)
  * Example: [Manipulating DOM Documents with phpQuery](https://codingexplained.com/coding/php/manipulating-dom-documents-with-phpquery)
