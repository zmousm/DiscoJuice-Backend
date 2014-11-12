<?php 


/**
* Pipe
*/
class Pipe {
	
	public $id, $title, $feeds, $userid;


	function __construct($data) {
		

	}


	static function fromDB($data) {

		// print_r($data);
		// echo "----";
		$n = new Pipe();
		if (isset($data['id'])) $n->id = $data['id'];
		if (isset($data['title'])) $n->title = $data['title'];
		if (isset($data['feeds'])) $n->feeds = $data['feeds'];
		if (isset($data['userid'])) $n->userid = $data['userid'];
		return $n;
	}


	protected function getQueryItem($feed, $data) {
		$q = array(
			'feed' => $feed,
		);

		if (isset($data['includeEntityIDs']) && is_array($data['includeEntityIDs'])) {
			$q['entityId'] = array(
				'$in' => $data['includeEntityIDs'],
			);
		}
		if (isset($data['includeSubIDs']) && is_array($data['includeSubIDs'])) {
			$q['subID'] = array(
				'$in' => $data['includeSubIDs'],
			);
		}
		if (isset($data['excludeEntityIDs']) && is_array($data['excludeEntityIDs'])) {
			$q['entityId'] = array(
				'$nin' => $data['excludeEntityIDs'],
			);
		}
		if (isset($data['excludeSubIDs']) && is_array($data['excludeSubIDs'])) {
			$q['subID'] = array(
				'$nin' => $data['excludeSubIDs'],
			);
		}

	
		return $q;

	}

	public function getQuery() {

		// print_r($this);


		$i = array();

		foreach($this->feeds AS $feed => $def) {
			$i[] = $this->getQueryItem($feed, $def);
		}


		if (count($i) > 1) {

			return array(
				'$or' => $i
			);

		} else if (count($i) === 1) {

			return $i[0];

		} 
		return null;

	}


	public function getItems() {

		$query = $this->getQuery();
		return $query;

	}



}