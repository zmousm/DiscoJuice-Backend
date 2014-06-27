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

	function getView() {
		$res = array(
			'Resources' => array()
		);
		foreach($this->items AS $x) {
			$res['Resources'][] = $x->getView();
		}
		$res['count'] = count($this->items);

		return $res;
	}

}