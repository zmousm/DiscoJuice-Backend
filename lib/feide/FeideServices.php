<?php

/**
* Feide Services heneter info om Feide tjenester
*/
class FeideServices {
	
	protected $feideconfig;
	function __construct() {
		global $BASE;
		$this->feideconfig = json_decode(file_get_contents(dirname($BASE) . '/etc/feide.js'), TRUE);
		if ($this->feideconfig === NULL) throw new Exception('Errors in feide.js: ' . error_get_last());



	}


	/**
	 * Obtain a list of entityID based upon an kind ID and a entityID to KindID mapping file.
	 * 
	 * @param  [type] $id              [description]
	 * @param  [type] $entityIDmapping [description]
	 * @return [type]                  [description]
	 */
	function getEntityIDs($id, $entityIDmapping) {

		$entityIDs = array();
		foreach($entityIDmapping AS $entityID => $kindID) {
			if ($id == $kindID) {
				$entityIDs[] = $entityID;
			}
		}
		return $entityIDs;

	}

	function getStatistics() {

		$token = $this->feideconfig['token'];


		$date = new DateTime();
		$date->modify('-1 year');
		$start = $date->format('Y-m-d 00:00:00');
		$amount = 52;
		$url = 'https://api.feide.no/stats/0/logins?&access_token=' . $token . '&readable&' . 
			'start=' . urlencode($start) . '&amount=' . $amount . '&unit=weeks&details';
		$rawdata = file_get_contents($url);
		$data = json_decode($rawdata, true);

		print_r($data['sp']);

		return $data['sp'];
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



		/**
		 * Load special file based upon Kind ID to entityID from kundeportalen / KIND.
		 */
		global $BASE;
		$mapFile = dirname($BASE) . '/etc/entityIDmapping.json';
		$entityIDmapping = json_decode(file_get_contents($mapFile), true);

		

		/**
		 * Load metadata...
		 */
		$metadataFile = dirname($BASE) . '/var/dynamic/metadata-prod/saml20-sp-remote.php';
		require_once($metadataFile);

		$metadataId2entityID = array();

		foreach($metadata AS $entityID => $meta) {
			if (isset($meta['feide:kindID'])) {
				if (!isset($metadataId2entityID[$meta['feide:kindID']])) {
					$metadataId2entityID[$meta['feide:kindID']] = array();
				}
				$metadataId2entityID[$meta['feide:kindID']][] = $entityID;
				// echo "entity id " . $meta['feide:kindID'] . '    ' . $entityID . "\n";
			}
			
		}
		// print_r($metadataId2entityID);
		// exit;


		$logocache = new LogoCache('feide');


		$items = array();
		foreach($services AS $k => $d) {

			// If we found a mapping from Kind ID to entities based upon metadat, use that
			if (isset($metadataId2entityID[$d['id']])) {

				$d['entityIDs'] = $metadataId2entityID[$d['id']];


			// If not, then use the mapping found in the external file.
			} else {

				$entityIDs = $this->getEntityIDs($d['id'], $entityIDmapping);
				if (!empty($entityIDs)) {
					$d['entityIDs'] = $entityIDs;
				} else {
					continue;
				}

				foreach($entityIDs AS $entityID) {
					if (isset($metadata[$entityID]) && isset($metadata[$entityID]['logo'])) {

							// feide/splogos/eu-supply_logo.jpg
						if (preg_match('|feide/splogos/(.*)|', $metadata[$entityID]['logo'], $matches)) {
							$d['logo'] = $matches[1];
						}
						
					}
				}

				
			}

			// $entityIDhash = sha1(join(',', $entityIDs));

			if (isset($d['logo'])) {
				// echo " " . $d['id'] . " logo => " . $d['logo'] . "\n";

				$src = dirname($BASE) . '/var/dynamic/splogos/' . $d['logo'];
				if (!file_exists($src)) {

					DiscoUtils::error('Could not find local logo file ' . $src);

				} else {

					$id = 'feide:sp:' . $d['id'];
					$meta = array(
						'type' => 'feide-sp'
					);
					if (count($entityIDs) === 1) {
						$meta['entityID'] = $entityIDs[0];
					}
					// $id, $src, $meta, $localFile = false)
					$ok = $logocache->getLogoURL($id, $src, $meta, true);

					// $ok = $logocache->getLogo($this->entityId, $this->feed, $cl);
					if (!empty($ok)) {
						$d['icon'] = $ok;	
					}

				}




			} else {
				DiscoUtils::debug('No logo available for ' . $d['id']);
				// echo " " . $d['id'] . " ----- ----- ----- \n";
			}



			// print_r($services[$k]);

			$item = new FeideService($d);
			$items[] = $item;

		}

		// exit;
	
		$stats = $this->getStatistics();
		foreach($items AS $item) {

			$entityIDs = $item->get('entityIDs');
			$total = 0;
			foreach($entityIDs AS $entityID) {
				if (isset($stats[$entityID])) {
					$total += $stats[$entityID];
				}
			}

			$item->set('statistics', $total);
			// $item->save();



			// print_r($item); exit;
		}


		
		// exit;





		foreach($items AS $item) {


			$item->save();
			// print_r($item); exit;
		}


	

	}
}

