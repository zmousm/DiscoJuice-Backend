<?php

require(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

$BASE = dirname(__FILE__). '/';

// Third party code
require_once($BASE . 'utils/phpterm.php');
require_once($BASE . 'utils/SimpleImage.php');

// SimpleSAMLphp libraries
require_once($BASE . 'ssp/SimpleSAML_Utilities.php');
require_once($BASE . 'ssp/SAMLParser.php');

// DiscoJuice code
require_once($BASE . 'Config.php'); 
require_once($BASE . 'GeoService.php');
require_once($BASE . 'LogoCache.php');
require_once($BASE . 'DiscoUtils.php');
require_once($BASE . 'DiscoStore.php');

// error_log( "Require discostore logos");
require_once($BASE . 'DiscoStoreLogos.php'); // Replace this with logostore.

require_once($BASE . 'Pipe.php');
require_once($BASE . 'LogoStore.php');
require_once($BASE . 'FeedItem.php');
require_once($BASE . 'Feed.php');
require_once($BASE . 'MetaLoader.php');
require_once($BASE . 'DiscoFeed.php');
require_once($BASE . 'DiscoJuiceBackend.php');


require_once($BASE . 'GateKeeperController.php');


Config::init();

