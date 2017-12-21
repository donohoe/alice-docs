# ArchieDocs

Quick and easy publishing with ArchieML from any Google Document.

## Sandbox & Local Development
```
mkdir ~/project
cd  ~/project
git clone git@github.com:donohoe/archie-docs.git
cd archie-docs/public
php -S localhost:2340
```

Browser:
http://localhost:2340/

```
cd ~/Dropbox/Development/md-archie-docs/public ; php -S localhost:2340 
```

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

There is a Google Dicument already Published to the web that demontartes styles and formats teh come through:

https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub



## Dependencies

* [phpQuery](https://github.com/punkave/phpQuery)
  * [Documentation](https://code.google.com/archive/p/phpquery/wikis/Manual.wiki)
