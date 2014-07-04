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
// require_once($BASE . 'DiscoStoreLogos.php'); // Replace this with logostore.
require_once($BASE . 'LogoStore.php');
require_once($BASE . 'FeedItem.php');
require_once($BASE . 'MetaLoader.php');
require_once($BASE . 'DiscoFeed.php');
require_once($BASE . 'DiscoJuiceBackend.php');


require_once($BASE . 'GateKeeperController.php');

require_once($BASE . 'feide/Models/Item.php');
require_once($BASE . 'feide/Favourites.php');
require_once($BASE . 'feide/Models/Collection.php');
require_once($BASE . 'feide/Models/ServiceCollection.php');
require_once($BASE . 'feide/Models/FeideService.php');
require_once($BASE . 'feide/Models/FeideOrg.php');
require_once($BASE . 'feide/KIND.php');
require_once($BASE . 'feide/FeedBuilder.php');
require_once($BASE . 'feide/FeideServices.php');
require_once($BASE . 'feide/FeideHelper.php');
require_once($BASE . 'feide/KommuneHelper.php');

require_once($BASE . 'FeideBackend.php');

Config::init();

