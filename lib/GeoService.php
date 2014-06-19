<?php

class GeoService {

	protected $countryTLDs = array(
		'ua.' => 'AU',
		'ac.' => 'CA',
		'zn.' => 'NZ',
		'lp.' => 'PL',
		'uh.' => 'HU',
		'es.' => 'SE',
		'se.' => 'ES',
		'ln.' => 'NL',
		'ei.' => 'IE',
		'rh.' => 'HR',
		'ed.' => 'DE',
		'rg.' => 'GR',
		'hc.' => 'CH',
		'if.' => 'FI',
		'zc.' => 'CZ',
		'rt.' => 'TR',
		'kd.' => 'DK',
		'on.' => 'NO',
		'ude.' => 'US',
		'ku.' => 'GB',
		'rb.' => 'BR',
	);

	protected $reader;

	function __construct() {
		global $BASE;
		$geoipfile = dirname($BASE). Config::get('geodatabase');


		// DiscoUtils::log('Loading GeoService', false);

		if (!class_exists('GeoIp2\Database\Reader')) {
			throw new Exception("Not properly loaded GeoIP library through composer.phar.");
		}

		if (!file_exists($geoipfile) ) {
			throw new Exception("Cannot find configured GeoIP database :");
		}

		try {

			// $reader = new GeoIp2\Database\Reader($THISPATH . 'var/GeoLite2-City.mmdb');
			$this->reader = new GeoIp2\Database\Reader($geoipfile); // 'var/GeoIP2-City.mmdb');
	

		} catch(Exception $e) {
			error_log("Error reading geo IP database.");
		}


	}




	public function countryFromIP($ip) {

		try {
			$record = $this->reader->city($ip);
			return $record->country->isoCode;
			// print_r($record->country->isoCode); exit;

			// $obj = array();
			// $obj['lat'] = $record->location->latitude;
			// $obj['lon'] = $record->location->longitude;
			// $obj['tz'] = $record->location->timeZone;
			// $tz = $obj['tz'];

		} catch(Exception $e) {
			// $tz = 'Europe/Amsterdam';
			error_log("Error looking up GeoIP for address: " . $ip);
		}
		
		return null;


	}

	public function geoFromIP($ip) {

		try {
			$record = $this->reader->city($ip);
			$geo = array(
				'lat' => $record->location->latitude,
				'lon' => $record->location->longitude
			);
			return $geo;
			// print_r($record->country->isoCode); exit;

			// $obj = array();
			// $obj['lat'] = $record->location->latitude;
			// $obj['lon'] = $record->location->longitude;
			// $obj['tz'] = $record->location->timeZone;
			// $tz = $obj['tz'];

		} catch(Exception $e) {
			// $tz = 'Europe/Amsterdam';
			error_log("Error looking up GeoIP for address: " . $ip);
		}
		return null;
	}

	function addrGeo($address) {
		
		// Temorary disabled. Needs to figure out a way to use a API key and add caching.
		return null;

		// if ($this->store->exists('geoaddr', $id, NULL)) {
		// 	// SimpleSAML_Logger::debug('IP Geo location (geo): Found ip [' . $ip . '] in cache.');
		// 	$stored =  $this->store->getValue('geoaddr', $id, NULL);
		// 	if ($stored === NULL) throw new Exception('Got negative cache for this Address');
		// 	return $stored;
		// }
		
		
		$url = 'http://maps.google.com/maps/geo?' . 
			'q=' . urlencode($address) . 
			'&key=ABQIAAAASglC3nGToDgiRPCcRfdVShS0II289t7QEnBTuvu6rL3UOFsbQRRRned7x9TQ1oaYxRr6qsA98J-tuA' .  
			'&sensor=false' . 
			'&output=json' . 
			'&oe=utf8';
		$res = file_get_contents($url);
		$result = json_decode($res);
		if ($result->Status->code !== 200) {
			// $this->store->set('geoaddr', $id, NULL, NULL);
			return NULL;
		}
		$location = array('lat' => $result->Placemark[0]->Point->coordinates[1], 'lon' => $result->Placemark[0]->Point->coordinates[0]);
		return $location;
	}




	public function countryFromURL($entityid) {
		try {
			$pu = parse_url($entityid, PHP_URL_HOST);			
			if (!empty($pu)) {
				$rh = strrev($pu); 
				DiscoUtils::debug('Looking up TLD : ' . $rh);				 
				foreach($this->countryTLDs AS $domain => $country) {
					if (DiscoUtils::prefix($domain, $rh)) {
						DiscoUtils::debug('Looking up TLD : ' . $rh . ' matched ' . $country);
						return $country;
					} 
				}
				DiscoUtils::debug('Looking up TLD : ' . $rh . ' DID NOT MATCH any');
			}	
		} catch(Exception $e) {

		}
		return null;
	}
		


}