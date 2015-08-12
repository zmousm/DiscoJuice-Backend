<?php

class DiscoJuiceBackend {

	protected $feedconfiglist;
	protected $enableCacheOnly = false;

	function __construct() {
		global $BASE;
		// $this->feedconfiglist = json_decode(file_get_contents(dirname($BASE) . '/etc/feeds.js'), TRUE);
		// if ($this->feedconfiglist === NULL) throw new Exception('Errors in feeds.js: ' . error_get_last());

		$this->loadFeed();

	}

	function getFeedConfig($id) {
		foreach($this->feedconfiglist AS $item) {
			if ($item['id'] === $id) {
				return $item;
			}
		}
		return null;
	}


	function loadFeed() {

		$ds = new DiscoStoreMongoDB();
		$this->feedconfiglist = $ds->getFeedList();

		// print_r($list);
		// exit;
		// return $list;

	}

	function enableCacheOnly($en) {
		$this->enableCacheOnly = $en;
	}

	function updateFeide() {

		


	}


	function updateFeed($feedId) {

		try {
			$config = $this->getFeedConfig($feedId);
			if ($config === null) {
				throw new Exception('Invalid feed identifier provided. No config found');
			}


			DiscoUtils::log('Update feed ' . $feedId, true);

			$metaloader = new MetaLoader($config['url'], $feedId, $this->enableCacheOnly);

			// $feedmeta = new sspmod_discojuice_MetaLoader($filecachedir, $feedconfig['url'], $id);
			// print_r($metaloader->getList()); exit;
			
			$feed = new DiscoFeed($feedId, $config, $metaloader->getList());
			$feed->process();


			// if (!empty($feedconfig['country'])) {
			// 	$feed->defaultCountry = $feedconfig['country'];
			// }
			// 
			//	print_r($feedmeta->getList());	c
			//	print_r($feed->getJSON());
			
			// $acl = array_merge($acl, $feedmeta->getDiscoveryLocations());
			// $feed->store($id);

		} catch(Exception $e) {
			DiscoUtils::error("Error processing feed [" . $feedId . "]");
			DiscoUtils::error($e->getMessage());
			print_r($e);

			// print_r($e);
		}

	}

	function update() {

		foreach($this->feedconfiglist AS $feedconfig) {

			$id = $feedconfig['id'];
			$this->updateFeed($id);


		}


	}

	
}