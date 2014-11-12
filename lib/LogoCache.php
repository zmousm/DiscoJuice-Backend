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
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// curl_setopt($ch, CURLOPT_SSLVERSION, 3);

    $output = curl_exec($ch);


    if ($output === false) {
    	$err = curl_error($ch);
    	DiscoUtils::error('Error downloading from [' . $Url . '] ' . $err);
    }
    curl_close($ch);

    return $output;
}


class LogoCache {

	protected $CACHELOGO =  86400; // 86400 ; // 60*60*24;
	protected $logocachedir;

	function __construct($dbname = 'discojuice') {
		$this->logocachedir = Config::get('logocachedir'); // '/tmp/discojuice/logos/';
		$this->store = new LogoStore($dbname);
	}


	protected function getBaseFile($src) {
		$hash = sha1($src);
		return $this->logocachedir . $hash;
	}


	protected function getOrigCache($src) {

		$localFile = $this->getBaseFile($src) . '.orig';
		$url = self::isValidURL($src);

		DiscoUtils::debug('About to download logo from [' . $src . ']');
		DiscoUtils::debug('And will store locally at  ' . $localFile);


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

	protected function getAge($data) {
		$ts = time();
		if (isset($data['created'])) {
			$ts = (int)$data['created']->sec;			
		}
		if (isset($data['updated'])) {
			$ts = (int)$data['updated']->sec;
		}
		return time() - $ts;
	}




	public function getLogoURL($id, $src, $meta, $localFile = false) {
		// echo "About to get Logo with " . $id . "\n";
		$existing = $this->store->get($id, false);

		$data = array();
		if (self::isValidEmbedded($src)) {
			$data['src'] = 'embed:sha1:' . sha1($src);
		} else {
			$data['src'] = $src;
		}

	
		// If we got a logo in DB already...
		if ($existing !== null) {


			// Check if src is changed
			if ($data['src'] !== $existing['src']) {
				DiscoUtils::debug('Logo [src] is changed, attempting to update logo.');
				return $this->fetchAndStore($id, $src, $meta, $existing, $localFile);
			}

			// Check if it is recent or old.
			if ($this->getAge($existing) > $this->CACHELOGO) {
				DiscoUtils::debug('Logo is cached for longer than the MAX CACHE TIME, and will be fetched again');
				return $this->fetchAndStore($id, $src, $meta, $existing, $localFile);
			} else {
				DiscoUtils::debug('Logo is cached for less than the MAX CACHE TIME. Will not be updated yet.');
				return $id;
			}
		}

		// Now fetching for the first time.
		return $this->fetchAndStore($id, $src, $meta, null, $localFile);
	}






	protected function fetchAndStore($id, $src, $meta, $existing = null, $isLocalFile = false) {

		// if ($localFile) {

		// 	$cachefile = $this->getCachedLogoLocal($src);
		// 	if ($cachefile === null) return false;

		// } else {

			// $cachefile = $this->getCachedLogo($src);
			// if ($cachefile === null) return false;



		if (empty($src)) {
			return null;
		}

		if ($isLocalFile) {


			$localFile = $src;

		} else {

			// Obtain cache of original file...
			$localFile = $this->getOrigCache($src);
			if ($localFile === null) return null;
			if (empty($localFile)) return null;

		}




		DiscoUtils::debug('Successfully obtained local cache of remote [' . $src . '] file. Local file is stored at ' . $localFile);


		$file = $this->getBaseFile($src);
		DiscoUtils::debug('Preparing a resized file to be cached locally at ' . $file);


		$this->processOrigLocal($localFile, $file);




		$imagedata = file_get_contents($file);
		if ($imagedata === false) return false;

		$etag = sha1($imagedata);

		$data = $meta;
		$data['id'] = $id;
		$data['contentType'] = 'image/png';
		$data['logo'] = new MongoBinData($imagedata);
		$data['etag'] = $etag;

		if (self::isValidEmbedded($src)) {
			$data['src'] = 'embed:sha1:' . sha1($src);
		} else {
			$data['src'] = $src;
		}

		// echo "To Process data";
		// $data['logo'] = sha1($imagedata);
		// print_r($data); 
		// exit;


		if ($existing === null) {
			$ok = $this->store->insert($id, $data);
			DiscoUtils::debug('Logo is inserted in database for the first time. ' . tc_colored('INSERT', 'green'));
			return $id; //TODO check if saving was ok

		} else {

			if ($existing['etag'] === $etag) {
				DiscoUtils::debug('Generated logo is identical to the existing copy. Will NOT update database. ' . tc_colored('SKIP', 'cyan'));
				return $id; 
			} else {


				$ok = $this->store->update($id, $data);
				DiscoUtils::debug('Logo is updated in database, because logo has changed. ' . tc_colored('UPDATE', 'red'));
				return $id; //TODO check if saving was ok

			}

		}
		// Will never reach...
		return false;

	}


	protected function processOrigLocal($localFile, $file) {


		if (file_exists($file)) {
			// error_log('Found file (1): ' . $relfile);
			DiscoUtils::debug('Using already cached file : ' . $file);
			return $file;
		}
		
		// if ($logo['height'] > 40) {
			$image = new SimpleImage();
			$image->load($localFile);
			$image->resizeToHeight(38);
			$image->save($file);

			if (file_exists($file)) {
				DiscoUtils::debug("Successfully resized logo and stored a new cached file.");
				return $file;
			}	
		// }
		
		$orgimg = file_get_contents($localFile);
		file_put_contents($file, $orgimg);
		
		if (file_exists($file)) {
			DiscoUtils::debug('Using generated and cached file : ' . $file);
			return $file;
		}

	}





	protected static function isValidURL($src) {
		if (filter_var($src, FILTER_VALIDATE_URL) === FALSE) return null;

		// A valid URL
		$p = parse_url($src);
		if (!in_array(strtolower($p['scheme']), array('http', 'https'))) {
			DiscoUtils::debug('Skipping URL to logo because it is not a valid scheme. Only http and https is valid.');
			return null;
		}

		return $src;
	}



	protected static function isValidEmbedded($src) {
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




}