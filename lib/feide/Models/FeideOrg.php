<?php
/**
* Feide Org
*/
class FeideOrg extends Item {
	
	protected static $collection = 'orgs';

	function __construct($attr, $fromDB = false) {
		parent::__construct($attr, $fromDB);
	}

	public static function getByRealm($realm) {

		$dbconfig = Config::get('db');
		$client = new MongoClient($dbconfig);
		$db = $client->feide;


		// $res = array();
		$data = $db->{static::$collection}->findOne(array('realm' => $realm));
		// foreach($cursor AS $item) {
		// 	$res[] = self::fromDB($item);
		// }
		return $data;

	}

}



