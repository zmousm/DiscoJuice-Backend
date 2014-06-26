<?php



class DiscoStoreLogos {


	protected $db;

	function __construct() {
		$dbconfig = Config::get('db');
		$client = new MongoClient($dbconfig);
		$this->db = $client->discojuice;
	}


	protected function getQuery($entityId, $feed) {
		if (empty($entityId)) throw new Exception('Cannot create query without required parameter [entityId]');
		if (empty($feed)) throw new Exception('Cannot create query without required parameter [feed]');
		$query = array(
			'entityId' => $entityId,
			'feed' => $feed,
		);
		return $query;
	}

	function get($entityId, $feed, $includeData = false) {


		$fields = null;
		if (!$includeData) {
			$fields = array('entityId' => true, 'feed' => true, 'etag' => true, 'contentType' => true, 'created' => true, 'updated' => true, 'src' => true);
			$existing = $this->db->logos->findOne($this->getQuery($entityId, $feed), $fields);
			return $existing;
		}

		$existing = $this->db->logos->findOne($this->getQuery($entityId, $feed));
		return $existing;


	}



	function insert($data) {
		
		$data['created'] = new MongoDate();
		$this->db->logos->insert($data);

	}

	function update($data) {

		$data['update'] = new MongoDate();
		$this->db->logos->update($this->getQuery($data['entityId'], $data['feed']), $data);

	}


	// ----- o ----- o ----- o ----- o ----- o ----- o ----- o ----- o ----- o ----- o ----- o ----- o


/*

	function insert(FeedItem $item) {

		$data = $item->getJSON();
		$data['created'] = new MongoDate();

		$this->db->idps->insert($data);
		$this->db->idphistory->insert($data);

	}

	function update(FeedItem $item) {

		$data = $item->getJSON();
		$data['update'] = new MongoDate();

		$this->db->idps->update($this->getQuery($item), $data);
		$this->db->idphistory->insert($data);
	}

	function listFeedEntities($feed) {
		$query = array(
			'feed' => $feed
		);
		$cursor = $this->db->idps->find($query, array('entityId' => true));
		$entities = array();
		foreach($cursor AS $item) {
			$entities[] = $item['entityId'];
		}

		// print_r($entities);
		// exit;
		return $entities;
		
	}

	function insertOrUpdate(FeedItem $item) {


		$existing = $this->db->idps->findOne($this->getQuery($item));
		if ($existing !== null) {

			$existingItem = FeedItem::fromDB($existing);
			if ($existingItem->equalTo($item)) {
				DiscoUtils::log('No changes in metadata ' . tc_colored('SKIP', 'cyan') . ' ' . $item->entityId);
			} else {
				DiscoUtils::log('Metadata is changed, storing changes ' . tc_colored('UPDATE', 'red')  . ' ' . $item->entityId);
				$this->update($item);				
			}

		} else {
			DiscoUtils::log('Metadata is completely new ' . tc_colored('INSERT', 'green')  . ' ' . $item->entityId);
			$this->insert($item);
		}


	}

	function remove($feed, $entityId) {
		$query = array(
			'entityId' => $entityId,
			'feed' => $feed,
		);
		$this->db->idps->remove($query);

		$data = $query;
		$data['metadata'] = null;
		$data['disco'] = null;
		$data['update'] = new MongoDate();
		$this->db->idphistory->insert($data);

	}
*/




}