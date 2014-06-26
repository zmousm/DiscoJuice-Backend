<?php

/**
* Feide Services heneter info om Feide tjenester
*/
class FeideServices {
	
	function __construct() {
		



	}

	function update() {

			
		$items = array();


		$cachefile = '/tmp/feide-services.json';
		if (file_exists($cachefile)) {

			$services = json_decode(file_get_contents($cachefile), true);

		} else {

			
			$url = 'https://api.feide.no/spinfo/0/all';

			$data = file_get_contents($url);
			file_put_contents($cachefile, $data);

			$services = json_decode($data, true);
		}



		foreach($services AS $d) {

			print_r($d); exit;

			$item = new FeideService($d);
			$item->save();
			// print_r($item); exit;
		}





		foreach($services AS $d) {

			$item = new FeideService($d);
			$item->save();

			// print_r($item); exit;
		}


	}
}

