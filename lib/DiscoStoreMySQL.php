<?php


class DiscoStoreMySQL extends DiscoStore {


	protected $db;

	function __construct() {
		$dbconfig = Config::get('db');
		$this->db = new mysqli($dbconfig["host"], $dbconfig["user"], $dbconfig["password"], $dbconfig["database"]);
		if ($this->db->connect_errno) {
			throw new Exception("Failed to connect to MySQL: (" . $this->db->connect_errno . ") " . $this->db->connect_error);
		}
	}


	protected function getQuery(FeedItem $item) {
		$query = array(
			'entityId' => $item->entityId,
			'feed' => $item->feed,
		);
		return $query;
	}

	function logProcess($feedId, $timer) {
		
		throw new Exception("not implemented");

		$data = array(
			'timestamp' => new MongoDate(),
			'time' => $timer,
			'feed' => $feedId,
		);
		$this->db->workerlog->insert($data);
	}

	function insert(FeedItem $item) {

		throw new Exception("not implemented");

		$data = $item->getJSON();
		$data['created'] = new MongoDate();

		$this->db->idps->insert($data);
		$this->db->idphistory->insert($data);

	}

	function update(FeedItem $item) {

		throw new Exception("not implemented");

		$data = $item->getJSON();
		$data['update'] = new MongoDate();

		$this->db->idps->update($this->getQuery($item), $data);
		$this->db->idphistory->insert($data);
	}

	function listFeedEntities($feed) {

		throw new Exception("not implemented");

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

		throw new Exception("not implemented");

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

		throw new Exception("not implemented");

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


	function getFeed($feed) {

		throw new Exception("not implemented");

		$query = array(
			'feed' => $feed
		);
		$cursor = $this->db->idps->find($query);
		$entities = array();
		foreach($cursor AS $item) {
			$n = $item['disco'];
			$n['entityId'] = $item['entityId'];
			$entities[] = $n;
		}
		return $entities;

	}
	function getFeedMetadata($feed) {

		throw new Exception("not implemented");

		$query = array(
			'feed' => $feed
		);
		$cursor = $this->db->idps->find($query);
		$entities = array();
		foreach($cursor AS $item) {
			$n = $item['metadata'];
			unset($n['entityDescriptor']);
			// $n['entityId'] = $item['entityId'];
			$entities[] = $n;
		}
		return $entities;

	}

	function getPipe($id) {

		throw new Exception("not implemented");

		$query = array(
			'id' => $id
		);
		$pipe = $this->db->pipes->findOne($query);
		return $pipe;
	}

	function getFeedList() {

		throw new Exception("not implemented");

		$query = array();
		$cursor = $this->db->feeds->find($query);
		$feeds = array();
		foreach($cursor AS $item) {
			$feeds[] = $item;
		}
		return $feeds;
	}

	function getIdPs($query) {

		throw new Exception("not implemented");

		$cursor = $this->db->idps->find($query);
		$entities = array();
		foreach($cursor AS $item) {
			$n = $item['disco'];
			$n['entityId'] = $item['entityId'];
			$entities[] = $n;
		}
		return $entities;
	}

	function insertOrUpdateFeed($item) {

		throw new Exception("not implemented");

		$query = array(
			'id' => $item['id'],
		);
		$existing = $this->db->feeds->findOne($query);
		if ($existing !== null) {


			foreach($item AS $k => $v) {
				$existing[$k] = $v;
			}

			$existing['update'] = new MongoDate();

			$this->db->feeds->update($query, $existing);
			DiscoUtils::log('Updating feed config ' . tc_colored('UPDATE', 'red')  . ' ' . $item['id']);			

		} else {

			$item['created'] = new MongoDate();
			$this->db->feeds->insert($item);
			DiscoUtils::log('Adding new metadata feed ' . tc_colored('INSERT', 'green')  . ' ' . $item['id']);
		}

	}

}