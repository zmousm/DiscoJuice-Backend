<?php


class CountriesLoader {

	/**
	 * The list contains fetched countries.
	 * @var [type]
	 */
	private $list;

	//Options
	protected $url = 'https://github.com/mledoze/countries/raw/master/countries.json';
	protected $cachedir = null;
	protected $cachefile = '.countries.json';
	protected $CACHECOUNTRIES = 86400; // 86400 ; // 60*60*24;

	protected $tld_overrides = array(
		'.fr' => 'FR',
		'.us' => 'US',
		'.edu' => 'US',
	);

	function __construct($enableCacheOnly = false) {

		$this->cachedir = Config::get('cachedir');

		if (!is_dir($this->cachedir)) throw new Exception('Cache dir not present');
		if (!is_writable($this->cachedir)) throw new Exception('Cache dir not writable');
		$cachefile = $this->cachedir . $this->cachefile;

		try {
			if (!$enableCacheOnly) {

				if (time() - filemtime($cachefile) > $this->CACHECOUNTRIES) {
					DiscoUtils::debug('Downloading countries from ' . tc_colored($this->url, 'green') . " and storing cache at " . tc_colored($cachefile, 'green'));
					$data = @file_get_contents($this->url);
					if ($data === false) {
						throw new Exception('Error retrieving countries from ' . $this->url);
					}
					file_put_contents($cachefile, $data);
				}
			} else {
				DiscoUtils::debug('Looking up cached countries from ' . tc_colored($cachefile, 'green'));
			}
		} catch (Exception $e) {
			error_log('Error updating countries from source ' . $this->url . ' : ' . $e->getMessage());
		}

		if (!file_exists($cachefile)) {
			throw new Exception('Not able to continue processing countries, because cannot read cached file');
		}

		DiscoUtils::debug('Countries JSON ready, starting to parse document');

		if (!isset($data)) {
			$data = @file_get_contents($cachefile);
		}
		$countries = json_decode($data);

		$cca2_tld = array();

		foreach($countries as $c) {
			if (empty($c->tld)) {
				continue;
			}
			$tldr = strrev($c->tld[0]);
			$cca2_tld[$c->cca2] = $tldr;
		}

		$this->list = array_merge(array_flip($cca2_tld),
			DiscoUtils::array_strrev_keys($this->tld_overrides));

		ksort($this->list);
	}
	function getList() {
		return $this->list;
	}
}
