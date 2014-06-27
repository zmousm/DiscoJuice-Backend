<?php



class LogoStore {


	protected $db;

	function __construct($dbname = 'discojuice') {
		$dbconfig = Config::get('db');
		$client = new MongoClient($dbconfig);
		$this->db = $client->{$dbname};
	}


	// protected function getQuery($entityId, $feed) {
	// 	if (empty($entityId)) throw new Exception('Cannot create query without required parameter [entityId]');
	// 	if (empty($feed)) throw new Exception('Cannot create query without required parameter [feed]');
	// 	$query = array(
	// 		'entityId' => $entityId,
	// 		'feed' => $feed,
	// 	);
	// 	return $query;
	// }

	public function get($id, $includeData = false) {


		$fields = null;
		if (!$includeData) {
			$fields = array('logo' => 0);
			$existing = $this->db->logos->findOne(array('id' => $id), $fields);
			return $existing;
		}

		$existing = $this->db->logos->findOne(array('id' => $id));
		return $existing;

	}



	function insert($id, $data) {
		$data['id'] = $id;
		$data['created'] = new MongoDate();
		$this->db->logos->insert($data);

	}

	function update($id, $data) {
		$data['id'] = $id;
		$data['update'] = new MongoDate();
		$this->db->logos->update(array('id' => $id), $data);

	}




}

