<?php

/**
* Feide Servcie
*/
class FeideService extends Item {
	
	protected static $collection = 'services';

	function __construct($attr, $fromDB = false) {
		parent::__construct($attr);
	}
}



