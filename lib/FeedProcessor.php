<?php


class FeedProcessor {
	
	protected $feed;
	protected static $langCache = [];

	function __construct($feed) {
		$this->feed = $feed;
	}


	/*
	 * Source:
	 * http://codereview.stackexchange.com/questions/9141/language-detection-php-script
	 */
	static function get_browser_language($available_languages, $http_accept_language = 'auto') {
		
		$cachestr = join('|', $available_languages);
		if (isset(self::$langCache[$cachestr])) {
			return self::$langCache[$cachestr];
		}
		// TODO : an accept header of 'no' should aid in preferring 'nb'. 
		// Need to support some kind of alias for this
		if ($http_accept_language == 'auto') {
			$http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		$pattern = '/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i';
		preg_match_all($pattern, $http_accept_language, $hits, PREG_SET_ORDER);
		$bestlang = $available_languages[0];
		$bestqval = 0;
		foreach ($hits as $arr) {
			$langprefix = strtolower ($arr[1]);
			if (!empty($arr[3])) {
				$langrange = strtolower ($arr[3]);
				$language = $langprefix . "-" . $langrange;
			}
			else $language = $langprefix;
			$qvalue = 1.0;
			if (!empty($arr[5])) $qvalue = floatval($arr[5]);
			// find q-maximal language
			if (in_array($language,$available_languages) && ($qvalue > $bestqval)) {
				$bestlang = $language;
				$bestqval = $qvalue;
			}
			// if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
			else if (in_array($langprefix,$available_languages) && (($qvalue*0.9) > $bestqval)) {
				$bestlang = $langprefix;
				$bestqval = $qvalue*0.9;
			}
		}
		// echo '<pre>'; print_r($hits); exit;
		self::$langCache[$cachestr] = $bestlang;
		return $bestlang;
	}

	function processItem($item) {
		$res = $item;

		if (is_array($item["title"])) {
			$avail = array_keys($item["title"]);
			// echo "<p>Avail is "; var_dump($avail);
			$lang = self::get_browser_language($avail);
			// echo "<p>res as "; var_dump($lang);

			$res["title"] = $res["title"][$lang];
	
		}
		$res["entityID"] = $res["entityId"];
		unset($res["entityId"]);
		
		return $res;
	}

	function process() {

		$res = [];

		foreach($this->feed AS $item) {

			$res[] = $this->processItem($item);

		}
		return $res;

	}


}