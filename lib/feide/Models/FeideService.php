<?php

/**
* Feide Servcie
*/
class FeideService extends Item {
	
	protected static $collection = 'services';

	function __construct($attr, $fromDB = false) {
		parent::__construct($attr);
	}


	function isAllowed($realm) {
		// echo "<pre>Checking for " . $realm . " where "; print_r($this->attr);
		if (!isset($this->attr['subscribers'])) return false;
		if (in_array($realm, $this->attr['subscribers'])) return true;
		return false;
	}

	function getByRealm($realm) {

		$all = self::getAll();

		$res = array();
		foreach($all AS $i) {
			if ($i->isAllowed($realm)) {
				$res[] = $i;
			}
		}

		return $res;
	}


}



