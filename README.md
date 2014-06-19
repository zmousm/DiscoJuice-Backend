# DiscoJuice Backend

Generates DiscoJuice JSON from SAML 2.0 XML Metadata, and pushes to a mongodb storage.


## Getting started


Configure MongoDB database in `etc/config.json`.

Configure which metadata feeds to parse in `etc/feeds.js`.


Run update.

	bin/update.php


Run update for a specific feed:

	bin/update.php -f kalmar

Do not download metadata file, but operate on cached file:

	bin/update.php -f kalmar --cache-only


