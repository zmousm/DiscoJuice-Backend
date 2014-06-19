<?php


function url_get_contents ($Url) {
    if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,2); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


class LogoCache {

	protected $CACHELOGO =  86400; // 86400 ; // 60*60*24;
	protected $logocachedir;

	function __construct() {
		$this->logocachedir = Config::get('logocachedir'); // '/tmp/discojuice/logos/';
		$this->store = new DiscoStoreLogos();
	}


	public function getBaseFile($src) {
		$hash = sha1($src);
		return $this->logocachedir . $hash;
	}


	public function getOrigCache($src) {

		$localFile = $this->getBaseFile($src) . '.orig';
		$url = self::isValidURL($src);
		if ($url !== null) {

			DiscoUtils::debug('Logo found on valid location: ' . $url);
			$imagecontent = url_get_contents($url);
			if (empty($imagecontent)) return null;
			file_put_contents($localFile, $imagecontent);
			DiscoUtils::debug('Successfully obtained logo from the url');
			return $localFile;

		}

		$imagecontent = self::isValidEmbedded($src);
		if ($imagecontent !== null) {

			file_put_contents($localFile, $imagecontent);
			DiscoUtils::debug('Successfully obtained logo from embedded in metadata and stored a local cache');
			return $localFile;

		}

		return null;

	}

	public function getAge($data) {
		$ts = (int)$data['created']->sec;
		if (isset($data['updated'])) {
			$ts = (int)$data['updated']->sec;
		}
		return time() - $ts;
	}


	public function getLogo($entityid, $feed, $logo) {

		if (empty($logo['url'])) {
			return null;
		}
		$logourl = $logo['url'];

		$existing = $this->store->get($entityid, $feed, false);
		if ($existing !== null) {

			// Check if src is changed
			if ($logourl !== $existing['src']) {
				DiscoUtils::debug('Logo [src] is changed, attempting to update logo.');
				return $this->fetchAndStore($entityid, $feed, $logo, $existing);
			}

			// Check if it is recent or old.
			if ($this->getAge($existing) > $this->CACHELOGO) {
				DiscoUtils::debug('Logo is cached for longer than the MAX CACHE TIME, and will be fetched again');
				return $this->fetchAndStore($entityid, $feed, $logo, $existing);
			} else {
				DiscoUtils::debug('Logo is cached for less than the MAX CACHE TIME. Will not be updated yet.');
				return true;
			}
		}


		// Now fetching for the first time.
		return $this->fetchAndStore($entityid, $feed, $logo);


	}

	public function fetchAndStore($entityId, $feed, $logo, $existing = null) {

		$cachefile = $this->getCachedLogo($logo);
		if ($cachefile === null) return false;

		$imagedata = file_get_contents($cachefile);
		if ($imagedata === false) return false;

		$etag = sha1($imagedata);

		$data = array(
			'entityId' => $entityId,
			'feed' => $feed,
			'src' => $logo['url'],
			'contentType' => 'image/png',
			'logo' => new MongoBinData($imagedata),
			'etag' => $etag,
		);

		if ($existing === null) {
			$ok = $this->store->insert($data);
			DiscoUtils::debug('Logo is inserted in database for the first time. ' . tc_colored('INSERT', 'green'));
			return true; //TODO check if saving was ok

		} else {

			if ($existing['etag'] === $etag) {
				DiscoUtils::debug('Generated logo is identical to the existing copy. Will NOT update database. ' . tc_colored('SKIP', 'cyan'));
				return true; 
			} else {


				$ok = $this->store->update($data);
				DiscoUtils::debug('Logo is updated in database, because logo has changed. ' . tc_colored('UPDATE', 'red'));
				return true; //TODO check if saving was ok

			}

		}
		// Will never reach...
		return false;

	}



	public function getCachedLogo($logo) {
		
		if (empty($logo['url'])) {
			return null;
		}


		$localFile = $this->getOrigCache($logo['url']);
		if ($localFile === null) return null;



		$file = $this->getBaseFile($logo['url']);
		DiscoUtils::debug('Preparing a resized file to be cached locally at ' . $file);

		if (file_exists($file)) {
			// error_log('Found file (1): ' . $relfile);
			DiscoUtils::debug('Using already cached file : ' . $file);
			return $file;
		}
		
		if ($logo['height'] > 40) {
			$image = new SimpleImage();
			$image->load($localFile);
			$image->resizeToHeight(38);
			$image->save($file);

			if (file_exists($file)) {
				DiscoUtils::debug("Successfully resized logo and stored a new cached file.");
				return $file;
			}	
		}
		
		$orgimg = file_get_contents($localFile);
		file_put_contents($file, $orgimg);
		
		if (file_exists($file)) {
			DiscoUtils::debug('Using generated and cached file : ' . $file);
			return $file;
		}
		
	}



	public static function isValidURL($src) {
		if (filter_var($src, FILTER_VALIDATE_URL) === FALSE) return null;

		// A valid URL
		$p = parse_url($src);
		if (!in_array(strtolower($p['scheme']), array('http', 'https'))) {
			DiscoUtils::debug('Skipping URL to logo because it is not a valid scheme. Only http and https is valid.');
			return null;
		}

		return $src;
	}



	public static function isValidEmbedded($src) {
		if (strpos($src, 'data:image') === 0) {
			$splitted = explode(',', $src);
			$check = base64_decode($splitted[1]);
			if ($check === false) {
				DiscoUtils::debug('Skipping logo containing a misformatted embedded logo');
				return null;
			}
			return $check;
		} else {
			return null;
		}
	}


	public static function getPreferredLogo($logos) {
		
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

}