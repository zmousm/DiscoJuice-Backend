<?php

class FeedItem {
	

	public $feed;
	public $entityId;
	protected $metadata;
	protected $disco = array();

	public $stored = false;

	protected $feedconfig;

	function __construct($entityId, $feed, $metadata, $feedconfig = null) {
		$this->entityId = $entityId;
		$this->metadata = $metadata;
		$this->feed = $feed;

		$this->feedconfig = $feedconfig;

		$this->geoservice = new GeoService();
	}

	public static function fromDB($data) {
		if (!isset($data['entityId'])) throw new Exception('Cannot create an FeedItem without the required entityId property');
		if (!isset($data['feed'])) throw new Exception('Cannot create an FeedItem without the required feed property');
		if (!isset($data['metadata'])) throw new Exception('Cannot create an FeedItem without the required metadata property');
		if (!isset($data['disco'])) throw new Exception('Cannot create an FeedItem without the required disco property');

		$new = new FeedItem($data['entityId'], $data['feed'], $data['metadata']);
		$new->setDisco($data['disco']);
		$new->stored = true;
		return $new;
	}

	public function setDisco($disco) {
		$this->disco = $disco;
	}

	public function compareMetadata(FeedItem $that) {

		$x1 = $this->metadata;
		unset($x1['expire']);
		unset($x1['entityDescriptor']);

		$x2 = $that->metadata;
		unset($x2['expire']);
		unset($x2['entityDescriptor']);

		return $x1 == $x2;
	}


	public function equalTo(FeedItem $that) {

		if ($this->entityId !== $that->entityId) return false;
		if ($this->feed !== $that->feed) return false;
		if ($this->disco !== $that->disco) return false;

		return $this->compareMetadata($that);
	}


	public function process() {

		$data = array();

		$this->getTitle($data, $this->metadata);
		$this->getCountry($data, $this->metadata);
		$this->getGeo($data, $this->metadata);
		$this->getLogo($data, $this->metadata);
		$this->getOverrides($data, $this->metadata);
		$this->disco = $data;
	}

	public function getJSON() {
		$res = array();
		$res['entityId'] = $this->entityId;
		$res['feed'] = $this->feed;
		$res['metadata'] = $this->metadata;
		$res['disco'] = $this->disco;
		return $res;
	}


	public function getOverrides(&$data, $m) {

		$entityId = $m['entityid'];
		if (!isset($this->feedconfig)) return;
		if (!isset($this->feedconfig['overrides'])) return;
		if (!isset($this->feedconfig['overrides'][$entityId])) return;

		DiscoUtils::log('Processing overrides for entity ' . $entityId);
		foreach($this->feedconfig['overrides'][$entityId] AS $k => $v) {
			DiscoUtils::debug('Adding override for [' . $k . '] on entityId ' . $entityId);
			$data[$k] = $v;
		}

	}


	public function getFilterMatch() {
		if (isset($this->feedconfig['FilterExpr']) &&
		is_string($this->feedconfig['FilterExpr'])) {
			$filterExpr = $this->feedconfig['FilterExpr'];
		} else {
			return true;
		}

		$entityId = $this->entityId;
		if (isset($this->disco['country']))
			$country = $this->disco['country'];
		$metadata = $this->metadata;
		$disco = $this->disco;

		$fexpr = 'return '. $filterExpr .';';
		return eval($fexpr);
	}



	/**
	 * Process metadata, and set a country tag to indicate which country this entry comes from.
	 * @param  [type] $data [description]
	 * @param  [type] $m    [description]
	 * @return [type]       [description]
	 */
	protected function getCountry(&$data, $m) {
		
		$countrytags = array(
			'croatia' => 'HR',
			'czech' => 'CZ',
			'denmark' => 'DK',
			'finland' => 'FI',
			'france' => 'FR',
			'germany' => 'DE',
			'greece' => 'GR',
			'ireland' => 'IE',
			'italy' => 'IT',
			'luxembourg' => 'LU',
			'hungary' => 'HU',
			'netherlands' => 'NL',
			'norway' => 'NO',
			'portugal' => 'PT',
			'poland' => 'PL',
			'slovenia' => 'SI',
			'spain' => 'ES',
			'sweden' => 'SE',
			'switzerland' => 'CH',
			'turkey' => 'TR',
			'us' => 'US',
			'uk' => 'GB',
			'japan'  => 'JP',
		);

		if (!empty($m['tags'])) {
			foreach($m['tags'] AS $tag) {
				if (array_key_exists($tag, $countrytags)) {
					$data['country'] = $countrytags[$tag];
					DiscoUtils::debug('Setting country from a metadata tag ' . $data['country']);
					return;
				}
			}
		}
		
		$c = $this->geoservice->countryFromURL($m['entityid']);
		if (!empty($c)) { 
			$data['country'] = $c; 
			DiscoUtils::debug('Setting country from entityid ' . $data['country']);
			return; 
		}

		// TODO default country
		if (!empty($this->feedconfig['country'])) {
			$data['country'] = $this->feedconfig['country'];
			DiscoUtils::debug('Setting country from default value ' . $data['country']);
			return;
		}

		if (!empty($m['SingleSignOnService']) ) {
			$m['metadata-set'] = 'saml20-idp-remote';
			$endpoint = DiscoUtils::getDefaultEndpoint($m['SingleSignOnService']);
			DiscoUtils::debug('Setting country from SingleSignOnService endpoint ' . $endpoint['Location']);

			$c = $this->geoservice->countryFromURL($endpoint['Location']);
			if (!empty($c)) { 
				$data['country'] = $c; 
				DiscoUtils::debug('Setting country from sso endpoint value ' . $data['country']);
				// return; 
			}				
			try {
				$host = parse_url($endpoint['Location'], PHP_URL_HOST);
				$ip = gethostbyname($host);

				DiscoUtils::debug('Setting country from sso endpoint IP address ' . $ip);

				$c = $this->geoservice->countryFromIP($ip);
				if (!empty($c)) { 
					$data['country'] = $c; 
					DiscoUtils::debug('Setting country from sso endpoint value ' . $data['country']);
					return; 
				}	

				// DiscoUtils::debug('Setting country from sso endpoint IP address ' . $c);
				// $capi = new sspmod_discojuice_Country($ip);
				// $region = $capi->getRegion();
				// if (preg_match('|^([A-Z][A-Z])/|', $region, $match)) {
				// 	$data['country'] = $match[1];
				// }
			} catch(Exception $e) {}			
		}

		DiscoUtils::error('Not able to set country for this item');

		return null;
	}




	protected function getTitle(&$data, $m) {
	
		
		// check for langauges...
		if(isset($m['name']) && is_array($m['name'])) {
			foreach($m['name'] AS $l => $n) {
				$this->languages[$l] = 1;
			}
		}
		
		// this is only a string, no language array.
		if(isset($m['name']) && is_string($m['name'])) {
			$data['title'] = array('en' => $m['name']);


		} else if(isset($m['name']) && is_array($m['name'])) {
			$data['title'] = $m['name'];

		} else if (isset($m['OrganizationName']) && is_array($m['OrganizationName'])) {
			$data['title'] = $m['OrganizationName'];

		} else {
			$data['title'] = array('en' => substr($m['entityid'], 0, 20));
			$data['weight'] = 9;
		}
		// echo "Got title: [" . $lang . "]" . $data['title'] . "\n";
	}



	protected static function parseGeolocationHint($g) {
		if (!is_array($g)) return null;
		$gi = $g[0];
		$gix = explode(':', $gi);
		if (count($gix) !== 2) return null;
		$gixx = $gix[1];
		$gid = explode(',', $gixx);
		if (count($gid) !== 2) return null;
		return array('lat' => $gid[0], 'lon' => $gid[1]);
	}

	protected function getGeo(&$data, $m) {
		
		if (!empty($m['DiscoHints']) && !empty($m['DiscoHints']['GeolocationHint'])) {
			$c = self::parseGeolocationHint($m['DiscoHints']['GeolocationHint']);
			if (!empty($c)) {
				$data['geo'] = $c;
				DiscoUtils::debug('Setting GeoLocation from DiscoHints/GeolocationHint extension in metadata ' . $data['geo']['lat'] . ' ' . $data['geo']['lon']);
				return;				
			}
		}
		
		if (!empty($data['title']) && !empty($data['title']['en']) && !empty($this->feedconfig['CountrySearch'])) {

			try {
				// $capi = new sspmod_discojuice_Country('158.38.130.37');
				$addr = $data['title']['en'] . ', ' . $this->feedconfig['CountrySearch'];
				DiscoUtils::debug('Looking up  ' . $addr);
				$geo = $this->geoservice->addrGeo($addr);
				
				if (!empty($geo)) {
					DiscoUtils::debug('Setting GeoLocation from google address lookup for  ' . $addr);
					$data['geo'] = $geo;
					return;
				}

				
			} catch (Exception $e) {
				
			}
			
		}
		

		if (!empty($m['SingleSignOnService']) ) {

			$m['metadata-set'] = 'saml20-idp-remote';
			$endpoint = DiscoUtils::getDefaultEndpoint($m['SingleSignOnService']);
			DiscoUtils::debug('Setting geo location from SingleSignOnService endpoint ' . $endpoint['Location']);
		
			try {
				$host = parse_url($endpoint['Location'], PHP_URL_HOST);
				$ip = gethostbyname($host);

				DiscoUtils::debug('Setting country from sso endpoint IP address ' . $ip);

				$c = $this->geoservice->geoFromIP($ip);
				if (!empty($c)) { 
					$data['geo'] = $c; 
					DiscoUtils::debug('Setting geo location from endpoint IP value ' . $data['country']);
					return; 
				}	

				// DiscoUtils::debug('Setting country from sso endpoint IP address ' . $c);
				// $capi = new sspmod_discojuice_Country($ip);
				// $region = $capi->getRegion();
				// if (preg_match('|^([A-Z][A-Z])/|', $region, $match)) {
				// 	$data['country'] = $match[1];
				// }
				
			} catch(Exception $e) {}	

		}

		
	}

	protected static function getPreferredLogo($logos) {
		
		$current = array('height' => 0);
		$found = false;
		
		foreach($logos AS $logo) {
			if (
					$logo['height'] > 23 && 
					$logo['height'] < 41 && 
					$logo['height'] > $current['height']
				) {
				$current = $logo;
				$found = true;
			}
		}
		if ($found) return $current;
		
		foreach($logos AS $logo) {
			if (
					$logo['height'] > $current['height']
				) {
				$current = $logo;
				$found = true;
			}
		}
		if ($found) return $current;
		
		return NULL;
		
	}







	protected function getLogo(&$data, $m) {

		$logocache = new LogoCache();


		// print_r($m);

		if (!empty($m['UIInfo']) && !empty($m['UIInfo']['Logo'])) {
			
			$cl = self::getPreferredLogo($m['UIInfo']['Logo']);
			
			// error_log('Preferred logo: ' . var_export($cl, true));
			
			

			if (!empty($cl)) {

				$src = $cl['url'];
				$id = sha1($this->feed . '|' . $this->entityId);
				$meta = array(
					'entityId' => $this->entityId,
					'feed' => $this->feed
				);
				$ok = $logocache->getLogoURL($id, $src, $meta);

				// $ok = $logocache->getLogo($this->entityId, $this->feed, $cl);
				if (!empty($ok)) {
					$data['icon'] = $ok;	
				}
				
				// $cached = $logocache->getCachedLogo($cl);
				// if (!empty($cached)) {
				// 	DiscoUtils::debug("Got logo stored locally: " . $cached);
				// 	// $data['icon'] = $cached;
				// }
			}

		} else {
			DiscoUtils::debug('Skipping logo for  ' . $data['title']['en'] . ' because it was missing');
		}
		
		
	}
	


}


