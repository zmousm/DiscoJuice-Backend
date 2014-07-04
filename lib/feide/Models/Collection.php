<?php

/**
* Collection
*/
class Collection
{
	
	protected $items;
	function __construct($items) {
		$this->items = $items;
	}

	function getView($opts = array()) {
		$res = array(
			'Resources' => array()
		);
		foreach($this->items AS $x) {
			$res['Resources'][] = $x->getView($opts);
		}
		$res['count'] = count($this->items);

		return $res;
	}

}

