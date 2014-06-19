<?php

class DiscoJuiceBackend {

	protected $feedconfiglist;
	protected $enableCacheOnly = false;

	function __construct() {
		global $BASE;
		$this->feedconfiglist = json_decode(file_get_contents(dirname($BASE) . '/etc/feeds.js'), TRUE);
		if ($this->feedconfiglist === NULL) throw new Exception('Errors in feeds.js: ' . error_get_last());

	}

	function enableCacheOnly($en) {
		$this->enableCacheOnly = $en;
	}


	function updateFeed($feedId) {

		try {
			if (!isset($this->feedconfiglist[$feedId])) {
				throw new Exception('Invalid feed identifier provided. No config found');
			}
			$config = $this->feedconfiglist[$feedId];


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

		foreach($this->feedconfiglist AS $id => $feedconfig) {


			$this->updateFeed($id);


		}


	}

	
}