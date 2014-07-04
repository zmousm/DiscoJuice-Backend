<?php

/**
* Feide Servcie
*
*
*	target:
*		higher_education
*		secondary_school
*		primary_school
*		other
* 
*/
class FeideService extends Item { 
	
	protected static $collection = 'services';

	function __construct($attr, $fromDB = false) {
		parent::__construct($attr, $fromDB);
	}


	function isAllowed($realm) {
		// echo "<pre>Checking for " . $realm . " where "; print_r($this->attr);
		if (!isset($this->attr['subscribers'])) return false;
		if (in_array($realm, $this->attr['subscribers'])) return true;
		return false;
	}

	function hasTarget($target) {
		return (in_array($target, $this->attr['target']));
	}

	// function getByRealm($realm) {

	// 	$all = self::getAll();

	// 	$res = array();
	// 	foreach($all AS $i) {
	// 		if ($i->isAllowed($realm)) {
	// 			$res[] = $i;
	// 		}
	// 	}

	// 	return $res;
	// }


	public function getTarget() {
		$data = array();

		foreach($this->attr['target'] AS $t) {
			$data[$t] = 1;
		}

		if (empty($data )) return null;
		return $data;
	}



	public function getView($opts = array()) {
		$data = parent::getView();
		if (isset($opts['realm'])) {
			$data['isAllowed'] = $this->isAllowed($opts['realm']);
		}
		$data['subscribers-count'] = 0;
		if (isset($data['subscribers'])) {
			$data['subscribers-count'] = count($data['subscribers']);	
		}
		$data['target'] = $this->getTarget();
		
		return $data;
	}

}



