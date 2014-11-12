<?php

class Feed {


	public $feed;
	public $entityId;
	protected $metadata;
	protected $disco = array();

	public $stored = false;


	function __construct($entityId, $feed, $metadata, $feedconfig = null) {
		$this->entityId = $entityId;
		$this->metadata = $metadata;
		$this->feed = $feed;

		$this->feedconfig = $feedconfig;

		$this->geoservice = new GeoService();
	}

	public static function fromDB($data) {
		if (!isset($data['id'])) throw new Exception('Cannot create an Feed without the required id property');
		if (!isset($data['url'])) throw new Exception('Cannot create an Feed without the required url property');

		$new = new Feed();
		$new->stored = true;
		return $new;
	}

	public static function toJSONlist($list) {
		$a = array();
		foreach($list AS $i) {
			$a[] = self::toJSON($i);
		}
		return $a;
	}

	public static function toJSON($data) {

		$item = array();


		foreach($data AS $k => $v) {
			if (in_array($k, array('id', 'title', 'descr', 'info', 'url', 'country', 'countrySearch', 'zoom', ))) {
				$item[$k] = $v;
			}
		}

		if (isset($data['created'])) {
			$item['created'] = (int)$data['created']->sec;
		}
		if (isset($data['updated'])) {
			$item['updated'] = (int)$data['updated']->sec;
		}
		return $item;


	}




}