<?php

/**
* Model
*/
class Item {
	
	protected $db;
	protected $loadedFromDB;

	protected static $collection = 'feide';

	public $attr;

	function __construct($attr = null, $fromDB = false) {

		$this->loadedFromDB = $fromDB;
		$this->attr = array();
		if ($attr !== null) {
			$this->attr = $attr;
		}

		$dbconfig = Config::get('db');
		$client = new MongoClient($dbconfig);
		$this->db = $client->feide;
		// print_r($this->db); exit;
	}

	public static function fromDB($attr) {
		unset($attr['_id']);
		$i = new static($attr, true);
		return $i;
	}


	public function getView() {
		return $this->attr;
	}
	public static function getAll() {

		$dbconfig = Config::get('db');
		$client = new MongoClient($dbconfig);
		$db = $client->feide;


		$res = array();
		$cursor = $db->{static::$collection}->find();
		foreach($cursor AS $item) {
			$res[] = self::fromDB($item);
		}
		return $res;
	}
	
	public function hasAttr($key) {
		return isset($this->attr[$key]);

	}

	public function get($key, $default = '__DEFAULT__') {
		if (isset($this->attr[$key])) {
			return $this->attr[$key];
		}
		if ($default !== '__DEFAULT__') return $default;
		throw new Exception('Cannot get key ['. $key . '] from item');
	}

	public function set($key, $value) {
		$this->attr[$key] = $value;
	}

	protected function getQuery() {
		if (empty($this->attr['id'])) throw new Exception('Missing [id]');
		$query = array(
			'id' => $this->attr['id']
		);
		return $query;
	}

	// function logProcess($feedId, $timer) {
	// 	$data = array(
	// 		'timestamp' => new MongoDate(),
	// 		'time' => $timer,
	// 		'feed' => $feedId,
	// 	);
	// 	$this->db->workerlog->insert($data);
	// }

	function getObj() {
		return $this->attr;
	}


	function insert() {

		$data = $this->getObj();
		$data['created'] = new MongoDate();

		$this->db->{static::$collection}->insert($data);
		// $this->db->idphistory->insert($data);

	}

	function update() {

		$data = $this->getObj();
		$data['created'] = new MongoDate();
		$data['updated'] = new MongoDate();

		$this->db->{static::$collection}->update($this->getQuery(), $data);
		// $this->db->idphistory->insert($data);
	}

	// function listFeedEntities($feed) {
	// 	$query = array(
	// 		'feed' => $feed
	// 	);
	// 	$cursor = $this->db->idps->find($query, array('entityId' => true));
	// 	$entities = array();
	// 	foreach($cursor AS $item) {
	// 		$entities[] = $item['entityId'];
	// 	}

	// 	// print_r($entities);
	// 	// exit;
	// 	return $entities;
		
	// }


	public function equalTo($that) {
		$x1 = $this->attr;
		unset($x1['created']);
		unset($x1['updated']);


		$x2 = $that->attr;
		unset($x2['created']);
		unset($x2['updated']);

		$modified = ($x1 == $x2);

		// print_r($x1);
		// print_r($x2);
		// echo "Is modified: " . var_export($modified, true) . "\n";



		return $x1 == $x2;
	}

	function save() {


		$existing = $this->db->{static::$collection}->findOne($this->getQuery());

		if ($existing !== null) {

			$existingItem = self::fromDB($existing);
			if ($this->equalTo($existingItem)) {
				DiscoUtils::log('No changes ' . tc_colored('SKIP', 'cyan') . ' ');
			} else {
				DiscoUtils::log('Object is modified, storing changes ' . tc_colored('UPDATE', 'red')  . ' ');
				$this->update();
			}

		} else {
			DiscoUtils::log('Metadata is completely new ' . tc_colored('INSERT', 'green')  . ' ');
			$this->insert();
		}

	}




	function remove() {
		$query = $this->getQuery();
		$this->db->{static::$collection}->remove($query);

		// $data = $query;
		// $data['metadata'] = null;
		// $data['disco'] = null;
		// $data['update'] = new MongoDate();
		// $this->db->idphistory->insert($data);

	}


}



