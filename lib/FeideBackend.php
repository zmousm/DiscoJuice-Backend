<?php

class FeideBackend {

	protected $feideconfig;
	protected $enableCacheOnly = false;

	function __construct() {
		global $BASE;
		$this->feideconfig = json_decode(file_get_contents(dirname($BASE) . '/etc/feide.js'), TRUE);
		if ($this->feideconfig === NULL) throw new Exception('Errors in feide.js: ' . error_get_last());

	}

	function enableCacheOnly($en) {
		$this->enableCacheOnly = $en;
	}




	function update() {

		$feidehelper = new FeideDiscoHelper($this->feideconfig);		
		// $list = $feidehelper->fetchOrgInfo();

	}




	
}