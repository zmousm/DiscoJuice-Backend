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




}

