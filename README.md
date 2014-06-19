# DiscoJuice Backend

Generates DiscoJuice JSON from SAML 2.0 XML Metadata, and pushes to a mongodb storage.

## Prerequisites

Install php5 and more

	apt-get install php5-cli php5-mcrypt php5-curl php5-gd

Download and locate DiscoJuice-Backend in `/var/DiscoJuice-Backend`.

Install composer

	cd /var/DiscoJuice-Backend
	curl -sS https://getcomposer.org/installer | php


Install external libraries

	./composer.phar install


## Getting started


Configure MongoDB database in `etc/config.json`.

Configure which metadata feeds to parse in `etc/feeds.js`.



## Running the backend

Run update.

	bin/update.php


Run update for a specific feed:

	bin/update.php -f kalmar

Do not download metadata file, but operate on cached file:

	bin/update.php -f kalmar --cache-only


