# ArchieDocs

Quick and easy publishing with ArchieML from any Google Document.

# Sandbox & Local Development

mkdir ~/project
cd  ~/project
git clone git@github.com:donohoe/archie-docs.git
cd archie-docs/public
php -S localhost:2340

Browser:
http://localhost:2340/


cd ~/Dropbox/Development/md-archie-docs/public ; php -S localhost:2340 


# Linking a Google Document:

Create a new Google Document by visiting this link:
https://docs.google.com/document/create

Copy/Paste this example into the document:

headline: This is it
leadin: Hello to the lead in.

kicker: blah

Go to: File > Publish to the web...
Click Publish
When asked to confirm, choose "OK"
Cop the URL that appears in as the new link
Click the "X" in top-right corner to close.


 : https://docs.google.com/document/d/e/2PACX-1vTXpFXuIQJimIJ6rsD13XC-MHJnpDlarlWiYsBoL0cYBkYyyT0l9LJ7RNfRreod7QLwqCCTdaixJZhe/pub



# Dependencies

https://github.com/4d47/php-archieml
phpQuery
