<?php

function discojuice_entity_cmp($a, $b) {
    if ($a['entityID'] == $b['entityID']) {
        return 0;
    }
    return ($a['entityID'] < $b['entityID']) ? -1 : 1;
}




class DiscoFeed {

	protected $feedId;
	protected $feedconfig;
	protected $metadata;

	protected $idplist;
	protected $store;
	
	function __construct($feedId, $feedconfig, $metadata) {

		$this->metadata = $metadata;
		$this->feedconfig = $feedconfig;
		$this->feedId = $feedId;
		
		$this->store = DiscoStore::getStore();

	}


	public function process() {

		$start = microtime(true);

		$this->idplist = array();
		$processedEntities = array();
		$i = 0;
		foreach($this->metadata AS $entityId => $metadataEntry) {

			// if (rand(0, 2) === 2) {
			// 	DiscoUtils::log('Skipping random entity: ' . $entityId); continue;
			// }

			DiscoUtils::log('Processing ' . $entityId);


			if (isset($metadataEntry["EntityAttributes"]) && isset($metadataEntry["EntityAttributes"]["http://macedir.org/entity-category"])) {
				if (in_array("http://refeds.org/category/hide-from-discovery", $metadataEntry["EntityAttributes"]["http://macedir.org/entity-category"])) {
					DiscoUtils::log('Skipping entry because refeds.org../hide-from-discovery ' . $entityId . "");
					continue;
				}
				
			}

			$entry = new FeedItem($entityId, $this->feedId, $metadataEntry, $this->feedconfig);
			$entry->process();
			$data = $entry->getJSON();
			
			// echo json_encode($data['disco'], JSON_PRETTY_PRINT) . "\n\n";

			$this->idplist[] = $entry;
			$processedEntities[] = $entityId;

			// if ($i++ > 5) break;
		}


		foreach($this->idplist AS $item) {
			$this->store->insertOrUpdate($item);
		}

		$existingEntries = $this->store->listFeedEntities($this->feedId);
		$toDelete = array_diff($existingEntries, $processedEntities);
		
		foreach($toDelete AS $td) {
			DiscoUtils::log('Removing entityId ' . $td);
			$this->store->remove($this->feedId, $td);
		}

		if (count($toDelete) === count($processedEntities)) {
			throw new Exception('Will not delete all entities. We assume there is a mistake somewhere... Please fix ASAP.');
		}

		if (count($toDelete) === 0) {
			DiscoUtils::log('All existing entities found, not removing any existing from ' . $this->feedId);
		}

		$end = microtime(true);
		$timer = $end - $start;

		$this->store->logProcess($this->feedId, $timer);

	}



}