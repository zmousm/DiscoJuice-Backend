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

Prepare cache directories:

	mkdir -p /tmp/discojuice/
	mkdir -p /tmp/discojuice/logos/


## Patch xmlseclibs

To handle large metadatafiles, [and get around a bug in php, patch xmlseclibs](https://09068716785457748500.googlegroups.com/attach/2f8a095b7c8d01d1/0001-xmlseclibs-Workaround-for-slow-canonicalization.patch?part=0.1&view=1&vt=ANaJVrEfixroqa1LcNBB26tPOyVJwBUCE5Gm2jxIidkLjzhAnOSeo2EUwPvZfeD7Dy9ftdJisZBAAUWi_btcbM-D2d3Ud1I-0qw2j-Zea88hIq-AunLsoj4).

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


