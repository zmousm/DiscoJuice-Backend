<?php

/**
 * Misc static functions that is used several places.in example parsing and id generation.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Utilities {

	/**
	 * List of log levels.
	 *
	 * This list is used to restore the log levels after some log levels are disabled.
	 *
	 * @var array
	 */
	private static $logLevelStack = array();


	/**
	 * The current mask of disabled log levels.
	 *
	 * Note: This mask is not directly related to the PHP error reporting level.
	 *
	 * @var int
	 */
	public static $logMask = 0;


	/**
	 * Will return sp.example.org
	 */
	public static function getSelfHost() {

		$url = self::getBaseURL();

		$start = strpos($url,'://') + 3;
		$length = strcspn($url,'/:',$start);

		return substr($url, $start, $length);

	}
	
	/**
	 * Retrieve Host value from $_SERVER environment variables
	 */
	private static function getServerHost() {

		if (array_key_exists('HTTP_HOST', $_SERVER)) {
			$currenthost = $_SERVER['HTTP_HOST'];
		} elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
			$currenthost = $_SERVER['SERVER_NAME'];
		} else {
			/* Almost certainly not what you want, but ... */
			$currenthost = 'localhost';
		}

		if(strstr($currenthost, ":")) {
				$currenthostdecomposed = explode(":", $currenthost);
				$port = array_pop($currenthostdecomposed);
				if (!is_numeric($port)) {
					array_push($currenthostdecomposed, $port);
                }
                $currenthost = implode($currenthostdecomposed, ":");
		}
		return $currenthost;

	}


	/**
	 * Will return https://sp.example.org[:PORT]
	 */
	public static function selfURLhost() {

		$url = self::getBaseURL();

		$start = strpos($url,'://') + 3;
		$length = strcspn($url,'/',$start) + $start;

		return substr($url, 0, $length);
	}

	
	/**
	 * This function checks if we should set a secure cookie.
	 *
	 * @return TRUE if the cookie should be secure, FALSE otherwise.
	 */
	public static function isHTTPS() {

		$url = self::getBaseURL();

		$end = strpos($url,'://');
		$protocol = substr($url, 0, $end);

		if ($protocol === 'https') {
			return TRUE;
		} else {
			return FALSE;
		}

	}

	/**
	 * retrieve HTTPS status from $_SERVER environment variables
	 */
	private static function getServerHTTPS() {

		if(!array_key_exists('HTTPS', $_SERVER)) {
			/* Not an https-request. */
			return FALSE;
		}

		if($_SERVER['HTTPS'] === 'off') {
			/* IIS with HTTPS off. */
			return FALSE;
		}

		/* Otherwise, HTTPS will be a non-empty string. */
		return $_SERVER['HTTPS'] !== '';

	}


	/**
	 * Retrieve port number from $_SERVER environment variables
	 * return it as a string such as ":80" if different from
	 * protocol default port, otherwise returns an empty string
	 */
	private static function getServerPort() {

		if (isset($_SERVER["SERVER_PORT"])) {
			$portnumber = $_SERVER["SERVER_PORT"];
		} else {
			$portnumber = 80;
		}
		$port = ':' . $portnumber;

		if (self::getServerHTTPS()) {
			if ($portnumber == '443') $port = '';
		} else {
			if ($portnumber == '80') $port = '';
		}

		return $port;

	}

	/**
	 * Will return https://sp.example.org/universities/ruc/baz/simplesaml/saml2/SSOService.php
	 */
	public static function selfURLNoQuery() {
	
		$selfURLhost = self::selfURLhost();
		$selfURLhost .= $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['PATH_INFO'])) {
			$selfURLhost .= $_SERVER['PATH_INFO'];
		}
		return $selfURLhost;
	
	}


	/**
	 * Will return sp.example.org/ssp/sp1
	 *
	 * Please note this function will return the base URL for the current
	 * SP, as defined in the global configuration.
	 */
	public static function getSelfHostWithPath() {
	
		$baseurl = explode("/", self::getBaseURL());
		$elements = array_slice($baseurl, 3 - count($baseurl), count($baseurl) - 4);
		$path = implode("/", $elements);
		$selfhostwithpath = self::getSelfHost();
		return $selfhostwithpath . "/" . $path;
	}
	
	/**
	 * Will return foo
	 */
	public static function getFirstPathElement($trailingslash = true) {
	
		if (preg_match('|^/(.*?)/|', $_SERVER['SCRIPT_NAME'], $matches)) {
			return ($trailingslash ? '/' : '') . $matches[1];
		}
		return '';
	}
	

	public static function selfURL() {

		$selfURLhost = self::selfURLhost();

		$requestURI = $_SERVER['REQUEST_URI'];
		if ($requestURI[0] !== '/') {
			/* We probably have a URL of the form: http://server/. */
			if (preg_match('#^https?://[^/]*(/.*)#i', $requestURI, $matches)) {
				$requestURI = $matches[1];
			}
		}

		return $selfURLhost . $requestURI;

	}



	/**
	 * Add one or more query parameters to the given URL.
	 *
	 * @param $url  The URL the query parameters should be added to.
	 * @param $parameter  The query parameters which should be added to the url. This should be
	 *                    an associative array. For backwards comaptibility, it can also be a
	 *                    query string representing the new parameters. This will write a warning
	 *                    to the log.
	 * @return The URL with the new query parameters.
	 */
	public static function addURLparameter($url, $parameter) {

		/* For backwards compatibility - allow $parameter to be a string. */
		if(is_string($parameter)) {
			/* Print warning to log. */
			$backtrace = debug_backtrace();
			$where = $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
			SimpleSAML_Logger::warning(
				'Deprecated use of SimpleSAML_Utilities::addURLparameter at ' .	$where .
				'. The parameter "$parameter" should now be an array, but a string was passed.');

			$parameter = self::parseQueryString($parameter);
		}
		assert('is_array($parameter)');

		$queryStart = strpos($url, '?');
		if($queryStart === FALSE) {
			$oldQuery = array();
			$url .= '?';
		} else {
			$oldQuery = substr($url, $queryStart + 1);
			if($oldQuery === FALSE) {
				$oldQuery = array();
			} else {
				$oldQuery = self::parseQueryString($oldQuery);
			}
			$url = substr($url, 0, $queryStart + 1);
		}

		$query = array_merge($oldQuery, $parameter);
		$url .= http_build_query($query, '', '&');

		return $url;
	}



	/**
	 * Get the ID and (optionally) a URL embedded in a StateID,
	 * in the form 'id:url'.
	 *
	 * @param string $stateId The state ID to use.
	 * @return array A hashed array with the ID and the URL (if any),
	 * in the 'id' and 'url' keys, respectively. If there's no URL
	 * in the input parameter, NULL will be returned as the value for
	 * the 'url' key.
	 */
	public static function parseStateID($stateId) {
		$tmp = explode(':', $stateId, 2);
		$id = $tmp[0];
		$url = NULL;
		if (count($tmp) === 2) {
			$url = $tmp[1];
		}
		return array('id' => $id, 'url' => $url);
	}


	public static function checkDateConditions($start=NULL, $end=NULL) {
		$currentTime = time();
	
		if (!empty($start)) {
			$startTime = SAML2_Utils::xsDateTimeToTimestamp($start);
			/* Allow for a 10 minute difference in Time */
			if (($startTime < 0) || (($startTime - 600) > $currentTime)) {
				return FALSE;
			}
		}
		if (!empty($end)) {
			$endTime = SAML2_Utils::xsDateTimeToTimestamp($end);
			if (($endTime < 0) || ($endTime <= $currentTime)) {
				return FALSE;
			}
		}
		return TRUE;
	}


	public static function generateID() {
		return '_' . self::stringToHex(self::generateRandomBytes(21));
	}
	

	/**
	 * This function generates a timestamp on the form used by the SAML protocols.
	 *
	 * @param $instant  The time the timestamp should represent.
	 * @return The timestamp.
	 */
	public static function generateTimestamp($instant = NULL) {
		if($instant === NULL) {
			$instant = time();
		}
		return gmdate('Y-m-d\TH:i:s\Z', $instant);
	}


	/**
	 * Interpret a ISO8601 duration value relative to a given timestamp.
	 *
	 * @param string $duration  The duration, as a string.
	 * @param int $timestamp  The unix timestamp we should apply the duration to. Optional, default
	 *                        to the current time.
	 * @return int  The new timestamp, after the duration is applied.
	 */
	public static function parseDuration($duration, $timestamp = NULL) {
		assert('is_string($duration)');
		assert('is_null($timestamp) || is_int($timestamp)');

		/* Parse the duration. We use a very strict pattern. */
		$durationRegEx = '#^(-?)P(?:(?:(?:(\\d+)Y)?(?:(\\d+)M)?(?:(\\d+)D)?(?:T(?:(\\d+)H)?(?:(\\d+)M)?(?:(\\d+)(?:[.,]\d+)?S)?)?)|(?:(\\d+)W))$#D';
		if (!preg_match($durationRegEx, $duration, $matches)) {
			throw new Exception('Invalid ISO 8601 duration: ' . $duration);
		}

		$durYears = (empty($matches[2]) ? 0 : (int)$matches[2]);
		$durMonths = (empty($matches[3]) ? 0 : (int)$matches[3]);
		$durDays = (empty($matches[4]) ? 0 : (int)$matches[4]);
		$durHours = (empty($matches[5]) ? 0 : (int)$matches[5]);
		$durMinutes = (empty($matches[6]) ? 0 : (int)$matches[6]);
		$durSeconds = (empty($matches[7]) ? 0 : (int)$matches[7]);
		$durWeeks = (empty($matches[8]) ? 0 : (int)$matches[8]);

		if (!empty($matches[1])) {
			/* Negative */
			$durYears = -$durYears;
			$durMonths = -$durMonths;
			$durDays = -$durDays;
			$durHours = -$durHours;
			$durMinutes = -$durMinutes;
			$durSeconds = -$durSeconds;
			$durWeeks = -$durWeeks;
		}

		if ($timestamp === NULL) {
			$timestamp = time();
		}

		if ($durYears !== 0 || $durMonths !== 0) {
			/* Special handling of months and years, since they aren't a specific interval, but
			 * instead depend on the current time.
			 */

			/* We need the year and month from the timestamp. Unfortunately, PHP doesn't have the
			 * gmtime function. Instead we use the gmdate function, and split the result.
			 */
			$yearmonth = explode(':', gmdate('Y:n', $timestamp));
			$year = (int)($yearmonth[0]);
			$month = (int)($yearmonth[1]);

			/* Remove the year and month from the timestamp. */
			$timestamp -= gmmktime(0, 0, 0, $month, 1, $year);

			/* Add years and months, and normalize the numbers afterwards. */
			$year += $durYears;
			$month += $durMonths;
			while ($month > 12) {
				$year += 1;
				$month -= 12;
			}
			while ($month < 1) {
				$year -= 1;
				$month += 12;
			}

			/* Add year and month back into timestamp. */
			$timestamp += gmmktime(0, 0, 0, $month, 1, $year);
		}

		/* Add the other elements. */
		$timestamp += $durWeeks * 7 * 24 * 60 * 60;
		$timestamp += $durDays * 24 * 60 * 60;
		$timestamp += $durHours * 60 * 60;
		$timestamp += $durMinutes * 60;
		$timestamp += $durSeconds;

		return $timestamp;
	}


	/**
	 * Show and log fatal error message.
	 *
	 * This function logs a error message to the error log and shows the
	 * message to the user. Script execution terminates afterwards.
	 *
	 * The error code comes from the errors-dictionary. It can optionally include parameters, which
	 * will be substituted into the output string.
	 *
	 * @param string $trackId  The trackid of the user, from $session->getTrackID().
	 * @param mixed $errorCode  Either a string with the error code, or an array with the error code and
	 *                          additional parameters.
	 * @param Exception $e  The exception which caused the error.
	 * @deprecated
	 */
	public static function fatalError($trackId = 'na', $errorCode = null, Exception $e = null) {

		throw new SimpleSAML_Error_Error($errorCode, $e);
	}


	/**
	 * Check whether an IP address is part of an CIDR.
	 */
	static function ipCIDRcheck($cidr, $ip = null) {
		if ($ip == null) $ip = $_SERVER['REMOTE_ADDR'];
		list ($net, $mask) = explode('/', $cidr);

		if (strstr($ip, ':') || strstr($net, ':')) {
			// Validate IPv6 with inet_pton, convert to hex with bin2hex
			// then store as a long with hexdec

			$ip_pack = inet_pton($ip);
			$net_pack = inet_pton($net);

			if ($ip_pack === false || $net_pack === false) {
				// not valid IPv6 address (warning already issued)
				return false;
			}

			$ip_ip = str_split(bin2hex($ip_pack),8);
			foreach ($ip_ip as &$value) {
				$value = hexdec($value);
			}

			$ip_net = str_split(bin2hex($net_pack),8);
			foreach ($ip_net as &$value) {
				$value = hexdec($value);
			}
		} else {
			$ip_ip[0] = ip2long ($ip);
			$ip_net[0] = ip2long ($net);
		}

		for($i = 0; $mask > 0 && $i < sizeof($ip_ip); $i++) {
			if ($mask > 32) {
				$iteration_mask = 32;
			} else {
				$iteration_mask = $mask;
			}
			$mask -= 32;

			$ip_mask = ~((1 << (32 - $iteration_mask)) - 1);

			$ip_net_mask = $ip_net[$i] & $ip_mask;
			$ip_ip_mask = $ip_ip[$i] & $ip_mask;

			if ($ip_ip_mask != $ip_net_mask)
				return false;
		}
		return true;
	}

	/*
	 * This is a temporary function, holding the redirect() functionality,
	 * meanwhile we are deprecating the it.
	 */
	private static function _doRedirect($url, $parameters = array()) {
		if (!empty($parameters)) {
			$url = self::addURLparameter($url, $parameters);
		}

		/* Set the HTTP result code. This is either 303 See Other or
		 * 302 Found. HTTP 303 See Other is sent if the HTTP version
		 * is HTTP/1.1 and the request type was a POST request.
		 */
		if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
			$_SERVER['REQUEST_METHOD'] === 'POST') {
			$code = 303;
		} else {
			$code = 302;
		}

		if (strlen($url) > 2048) {
			SimpleSAML_Logger::warning('Redirecting to a URL longer than 2048 bytes.');
		}

		/* Set the location header. */
		header('Location: ' . $url, TRUE, $code);

		/* Disable caching of this response. */
		header('Pragma: no-cache');
		header('Cache-Control: no-cache, must-revalidate');

		/* Show a minimal web page with a clickable link to the URL. */
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' .
			' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml">';
		echo '<head>
					<meta http-equiv="content-type" content="text/html; charset=utf-8">
					<title>Redirect</title>
				</head>';
		echo '<body>';
		echo '<h1>Redirect</h1>';
		echo '<p>';
		echo 'You were redirected to: ';
		echo '<a id="redirlink" href="' .
			htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
		echo '<script type="text/javascript">document.getElementById("redirlink").focus();</script>';
		echo '</p>';
		echo '</body>';
		echo '</html>';

		/* End script execution. */
		exit;
	} 


	/**
	 * This function redirects the user to the specified address.
	 *
	 * This function will use the "HTTP 303 See Other" redirection if the
	 * current request used the POST method and the HTTP version is 1.1.
	 * Otherwise, a "HTTP 302 Found" redirection will be used.
	 *
	 * The fuction will also generate a simple web page with a clickable
	 * link to the target page.
	 *
	 * @param string $url The URL we should redirect to. This URL may include
	 * query parameters. If this URL is a relative URL (starting with '/'),
	 * then it will be turned into an absolute URL by prefixing it with the
	 * absolute URL to the root of the website.
	 * @param string[] $parameters An array with extra query string parameters
	 * which should be appended to the URL. The name of the parameter is the
	 * array index. The value of the parameter is the value stored in the index.
	 * Both the name and the value will be urlencoded. If the value is NULL,
	 * then the parameter will be encoded as just the name, without a value.
 	 * @param string[] $allowed_redirect_hosts An array with a whitelist of
	 * hosts for which redirects are allowed. If NULL, redirections will be
	 * allowed to any host. Otherwise, the host of the $url provided must be
	 * present in this parameter. If the host is not whitelisted, an exception
	 * will be thrown.
	 *
	 * @return void This function never returns.
	 * @deprecated 1.12.0 This function will be removed from the API. Instead,
	 * use the redirectTrustedURL or redirectUntrustedURL functions
	 * accordingly.
	 */
	public static function redirect($url, $parameters = array(),
		$allowed_redirect_hosts = NULL) {
		
		assert(is_string($url));
		assert(strlen($url) > 0);
		assert(is_array($parameters));

		$url = self::normalizeURL($url);
		if ($allowed_redirect_hosts !== NULL) {
			$url = self::checkURLAllowed($url, $allowed_redirect_hosts);	
		}
		self::_doRedirect($url, $parameters);
	}

	/**
	 * This function redirects to the specified URL without performing
	 * any security checks. Please, do NOT use this function with user
	 * supplied URLs.
	 *
	 * This function will use the "HTTP 303 See Other" redirection if the
	 * current request used the POST method and the HTTP version is 1.1.
	 * Otherwise, a "HTTP 302 Found" redirection will be used.
	 *
	 * The fuction will also generate a simple web page with a clickable
	 * link to the target URL.
	 *
	 * @param string $url The URL we should redirect to. This URL may include
	 * query parameters. If this URL is a relative URL (starting with '/'),
	 * then it will be turned into an absolute URL by prefixing it with the
	 * absolute URL to the root of the website.
	 * @param string[] $parameters An array with extra query string parameters
	 * which should be appended to the URL. The name of the parameter is the
	 * array index. The value of the parameter is the value stored in the index.
	 * Both the name and the value will be urlencoded. If the value is NULL,
	 * then the parameter will be encoded as just the name, without a value.
	 *
	 * @return void This function never returns.
	 */
	public static function redirectTrustedURL($url, $parameters = array()) {
		$url = self::normalizeURL($url);
		self::_doRedirect($url, $parameters);
	}

	/**
	 * This function redirects to the specified URL after performing the
	 * appropriate security checks on it. Particularly, it will make sure that
	 * the provided URL is allowed by the 'redirect.trustedsites' directive
	 * in the configuration.
	 *
	 * If the aforementioned option is not set or the URL does correspond to a
	 * trusted site, it performs a redirection to it. If the site is not
	 * trusted, an exception will be thrown.
	 *
	 * See the redirectTrustedURL function for more details.
	 * 
	 * @return void This function never returns.
	 */
	public static function redirectUntrustedURL($url, $parameters = array()) {
		$url = self::checkURLAllowed($url);
		self::_doRedirect($url, $parameters);
	}

	/**
	 * This function transposes a two-dimensional array, so that
	 * $a['k1']['k2'] becomes $a['k2']['k1'].
	 *
	 * @param $in   Input two-dimensional array.
	 * @return      The transposed array.
	 */
	public static function transposeArray($in) {
		assert('is_array($in)');

		$ret = array();

		foreach($in as $k1 => $a2) {
			assert('is_array($a2)');

			foreach($a2 as $k2 => $v) {
				if(!array_key_exists($k2, $ret)) {
					$ret[$k2] = array();
				}

				$ret[$k2][$k1] = $v;
			}
		}

		return $ret;
	}


	/**
	 * This function checks if the DOMElement has the correct localName and namespaceURI.
	 *
	 * We also define the following shortcuts for namespaces:
	 * - '@ds':      'http://www.w3.org/2000/09/xmldsig#'
	 * - '@md':      'urn:oasis:names:tc:SAML:2.0:metadata'
	 * - '@saml1':   'urn:oasis:names:tc:SAML:1.0:assertion'
	 * - '@saml1md': 'urn:oasis:names:tc:SAML:profiles:v1metadata'
	 * - '@saml1p':  'urn:oasis:names:tc:SAML:1.0:protocol'
	 * - '@saml2':   'urn:oasis:names:tc:SAML:2.0:assertion'
	 * - '@saml2p':  'urn:oasis:names:tc:SAML:2.0:protocol'
	 *
	 * @param $element The element we should check.
	 * @param $name The localname the element should have.
	 * @param $nsURI The namespaceURI the element should have.
	 * @return TRUE if both namespace and localname matches, FALSE otherwise.
	 */
	public static function isDOMElementOfType(DOMNode $element, $name, $nsURI) {
		assert('is_string($name)');
		assert('is_string($nsURI)');
		assert('strlen($nsURI) > 0');

		if (!($element instanceof DOMElement)) {
			/* Most likely a comment-node. */
			return FALSE;
		}

		/* Check if the namespace is a shortcut, and expand it if it is. */
		if($nsURI[0] == '@') {

			/* The defined shortcuts. */
			$shortcuts = array(
				'@ds' => 'http://www.w3.org/2000/09/xmldsig#',
				'@md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
				'@saml1' => 'urn:oasis:names:tc:SAML:1.0:assertion',
				'@saml1md' => 'urn:oasis:names:tc:SAML:profiles:v1metadata',
				'@saml1p' => 'urn:oasis:names:tc:SAML:1.0:protocol',
				'@saml2' => 'urn:oasis:names:tc:SAML:2.0:assertion',
				'@saml2p' => 'urn:oasis:names:tc:SAML:2.0:protocol',
				'@shibmd' => 'urn:mace:shibboleth:metadata:1.0',
				);

			/* Check if it is a valid shortcut. */
			if(!array_key_exists($nsURI, $shortcuts)) {
				throw new Exception('Unknown namespace shortcut: ' . $nsURI);
			}

			/* Expand the shortcut. */
			$nsURI = $shortcuts[$nsURI];
		}


		if($element->localName !== $name) {
			return FALSE;
		}

		if($element->namespaceURI !== $nsURI) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * This function finds direct descendants of a DOM element with the specified
	 * localName and namespace. They are returned in an array.
	 *
	 * This function accepts the same shortcuts for namespaces as the isDOMElementOfType function.
	 *
	 * @param DOMElement $element  The element we should look in.
	 * @param string $localName  The name the element should have.
	 * @param string $namespaceURI  The namespace the element should have.
	 * @return array  Array with the matching elements in the order they are found. An empty array is
	 *         returned if no elements match.
	 */
	public static function getDOMChildren(DOMElement $element, $localName, $namespaceURI) {
		assert('is_string($localName)');
		assert('is_string($namespaceURI)');

		$ret = array();

		for($i = 0; $i < $element->childNodes->length; $i++) {
			$child = $element->childNodes->item($i);

			/* Skip text nodes and comment elements. */
			if($child instanceof DOMText || $child instanceof DOMComment) {
				continue;
			}

			if(self::isDOMElementOfType($child, $localName, $namespaceURI) === TRUE) {
				$ret[] = $child;
			}
		}

		return $ret;
	}


	/**
	 * This function extracts the text from DOMElements which should contain
	 * only text content.
	 *
	 * @param $element The element we should extract text from.
	 * @return The text content of the element.
	 */
	public static function getDOMText($element) {
		assert('$element instanceof DOMElement');

		$txt = '';

		for($i = 0; $i < $element->childNodes->length; $i++) {
			$child = $element->childNodes->item($i);
			if(!($child instanceof DOMText)) {
				throw new Exception($element->localName . ' contained a non-text child node.');
			}

			$txt .= $child->wholeText;
		}

		$txt = trim($txt);
		return $txt;
	}


	/**
	 * This function parses the Accept-Language http header and returns an associative array with each
	 * language and the score for that language.
	 *
	 * If an language includes a region, then the result will include both the language with the region
	 * and the language without the region.
	 *
	 * The returned array will be in the same order as the input.
	 *
	 * @return An associative array with each language and the score for that language.
	 */
	public static function getAcceptLanguage() {

		if(!array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
			/* No Accept-Language header - return empty set. */
			return array();
		}

		$languages = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));

		$ret = array();

		foreach($languages as $l) {
			$opts = explode(';', $l);

			$l = trim(array_shift($opts)); /* The language is the first element.*/

			$q = 1.0;

			/* Iterate over all options, and check for the quality option. */
			foreach($opts as $o) {
				$o = explode('=', $o);
				if(count($o) < 2) {
					/* Skip option with no value. */
					continue;
				}

				$name = trim($o[0]);
				$value = trim($o[1]);

				if($name === 'q') {
					$q = (float)$value;
				}
			}

			/* Remove the old key to ensure that the element is added to the end. */
			unset($ret[$l]);

			/* Set the quality in the result. */
			$ret[$l] = $q;

			if(strpos($l, '-')) {
				/* The language includes a region part. */

				/* Extract the language without the region. */
				$l = explode('-', $l);
				$l = $l[0];

				/* Add this language to the result (unless it is defined already). */
				if(!array_key_exists($l, $ret)) {
					$ret[$l] = $q;
				}
			}
		}

		return $ret;
	}




    /**
     * @deprecated
     * @param int $length The amount of random bytes to generate.
     * @return string A string of $length random bytes.
     */
    public static function generateRandomBytesMTrand($length) {
	
		/* Use mt_rand to generate $length random bytes. */
		$data = '';
		for($i = 0; $i < $length; $i++) {
			$data .= chr(mt_rand(0, 255));
		}

		return $data;
	}


	/**
	 * This function generates a binary string containing random bytes.
	 *
	 * It is implemented as a wrapper of the openssl_random_pseudo_bytes function,
     * available since PHP 5.3.0.
	 *
	 * @param int $length The number of random bytes to return.
     * @param boolean $fallback Deprecated.
	 * @return string A string of $length random bytes.
	 */
	public static function generateRandomBytes($length, $fallback = TRUE) {
		assert('is_int($length)');

        return openssl_random_pseudo_bytes($length);
	}


	/**
	 * This function converts a binary string to hexadecimal characters.
	 *
	 * @param $bytes  Input string.
	 * @return String with lowercase hexadecimal characters.
	 */
	public static function stringToHex($bytes) {
		$ret = '';
		for($i = 0; $i < strlen($bytes); $i++) {
			$ret .= sprintf('%02x', ord($bytes[$i]));
		}
		return $ret;
	}


	/**
	 * Resolve a (possibly) relative URL relative to a given base URL.
	 *
	 * This function supports these forms of relative URLs:
	 *  ^\w+: Absolute URL
	 *  ^// Same protocol.
	 *  ^/ Same protocol and host.
	 *  ^? Same protocol, host and path, replace query string & fragment
	 *  ^# Same protocol, host, path and query, replace fragment
	 *  The rest: Relative to the base path.
	 *
	 * @param $url  The relative URL.
	 * @param $base  The base URL. Defaults to the base URL of this installation of simpleSAMLphp.
	 * @return An absolute URL for the given relative URL.
	 */
	public static function resolveURL($url, $base = NULL) {
		if($base === NULL) {
			$base = self::getBaseURL();
		}

		if(!preg_match('/^((((\w+:)\/\/[^\/]+)(\/[^?#]*))(?:\?[^#]*)?)(?:#.*)?/', $base, $baseParsed)) {
			throw new Exception('Unable to parse base url: ' . $base);
		}

		$baseDir = dirname($baseParsed[5] . 'filename');
		$baseScheme = $baseParsed[4];
		$baseHost = $baseParsed[3];
		$basePath = $baseParsed[2];
		$baseQuery = $baseParsed[1];

		if(preg_match('$^\w+:$', $url)) {
			return $url;
		}

		if(substr($url, 0, 2) === '//') {
			return $baseScheme . $url;
		}

		$firstChar = substr($url, 0, 1);

		if($firstChar === '/') {
			return $baseHost . $url;
		}

		if($firstChar === '?') {
			return $basePath . $url;
		}

		if($firstChar === '#') {
			return $baseQuery . $url;
		}


		/* We have a relative path. Remove query string/fragment and save it as $tail. */
		$queryPos = strpos($url, '?');
		$fragmentPos = strpos($url, '#');
		if($queryPos !== FALSE || $fragmentPos !== FALSE) {
			if($queryPos === FALSE) {
				$tailPos = $fragmentPos;
			} elseif($fragmentPos === FALSE) {
				$tailPos = $queryPos;
			} elseif($queryPos < $fragmentPos) {
				$tailPos = $queryPos;
			} else {
				$tailPos = $fragmentPos;
			}

			$tail = substr($url, $tailPos);
			$dir = substr($url, 0, $tailPos);
		} else {
			$dir = $url;
			$tail = '';
		}

		$dir = self::resolvePath($dir, $baseDir);

		return $baseHost . $dir . $tail;
	}


	/**
	 * Normalizes a URL to an absolute URL and validate it.
	 *
	 * In addition to resolving the URL, this function makes sure that it is
	 * a link to a http or https site.
	 *
	 * @param string $url  The relative URL.
	 * @return string  An absolute URL for the given relative URL.
	 */
	public static function normalizeURL($url) {
		assert('is_string($url)');

		$url = SimpleSAML_Utilities::resolveURL($url, SimpleSAML_Utilities::selfURL());

		/* Verify that the URL is to a http or https site. */
		if (!preg_match('@^https?://@i', $url)) {
			throw new SimpleSAML_Error_Exception('Invalid URL: ' . $url);
		}

		return $url;
	}


	/**
	 * Parse a query string into an array.
	 *
	 * This function parses a query string into an array, similar to the way the builtin
	 * 'parse_str' works, except it doesn't handle arrays, and it doesn't do "magic quotes".
	 *
	 * Query parameters without values will be set to an empty string.
	 *
	 * @param $query_string  The query string which should be parsed.
	 * @return The query string as an associative array.
	 */
	public static function parseQueryString($query_string) {
		assert('is_string($query_string)');

		$res = array();
		foreach(explode('&', $query_string) as $param) {
			$param = explode('=', $param);
			$name = urldecode($param[0]);
			if(count($param) === 1) {
				$value = '';
			} else {
				$value = urldecode($param[1]);
			}

			$res[$name] = $value;
		}

		return $res;
	}


	/**
	 * Parse and validate an array with attributes.
	 *
	 * This function takes in an associative array with attributes, and parses and validates
	 * this array. On success, it will return a normalized array, where each attribute name
	 * is an index to an array of one or more strings. On failure an exception will be thrown.
	 * This exception will contain an message describing what is wrong.
	 *
	 * @param array $attributes  The attributes we should parse and validate.
	 * @return array  The parsed attributes.
	 */
	public static function parseAttributes($attributes) {

		if (!is_array($attributes)) {
			throw new Exception('Attributes was not an array. Was: ' . var_export($attributes, TRUE));
		}

		$newAttrs = array();
		foreach ($attributes as $name => $values) {
			if (!is_string($name)) {
				throw new Exception('Invalid attribute name: ' . var_export($name, TRUE));
			}

			if (!is_array($values)) {
				$values = array($values);
			}

			foreach ($values as $value) {
				if (!is_string($value)) {
					throw new Exception('Invalid attribute value for attribute ' . $name .
						': ' . var_export($value, TRUE));
				}
			}

			$newAttrs[$name] = $values;
		}

		return $newAttrs;
	}




	/**
	 * Retrieve last error message.
	 *
	 * This function retrieves the last error message. If no error has occurred,
	 * '[No error message found]' will be returned. If the required function isn't available,
	 * '[Cannot get error message]' will be returned.
	 *
	 * @return string  Last error message.
	 */
	public static function getLastError() {

		if (!function_exists('error_get_last')) {
			return '[Cannot get error message]';
		}

		$error = error_get_last();
		if ($error === NULL) {
			return '[No error message found]';
		}

		return $error['message'];
	}



	/**
	 * Get public key or certificate from metadata.
	 *
	 * This function implements a function to retrieve the public key or certificate from
	 * a metadata array.
	 *
	 * It will search for the following elements in the metadata:
	 * 'certData'  The certificate as a base64-encoded string.
	 * 'certificate'  A file with a certificate or public key in PEM-format.
	 * 'certFingerprint'  The fingerprint of the certificate. Can be a single fingerprint,
	 *                    or an array of multiple valid fingerprints.
	 *
	 * This function will return an array with these elements:
	 * 'PEM'  The public key/certificate in PEM-encoding.
	 * 'certData'  The certificate data, base64 encoded, on a single line. (Only
	 *             present if this is a certificate.)
	 * 'certFingerprint'  Array of valid certificate fingerprints. (Only present
	 *                    if this is a certificate.)
	 *
	 * @param SimpleSAML_Configuration $metadata  The metadata.
	 * @param bool $required  Whether the private key is required. If this is TRUE, a
	 *                        missing key will cause an exception. Default is FALSE.
	 * @param string $prefix  The prefix which should be used when reading from the metadata
	 *                        array. Defaults to ''.
	 * @return array|NULL  Public key or certificate data, or NULL if no public key or
	 *                     certificate was found.
	 */
	public static function loadPublicKey(SimpleSAML_Configuration $metadata, $required = FALSE, $prefix = '') {
		assert('is_bool($required)');
		assert('is_string($prefix)');

		$keys = $metadata->getPublicKeys(NULL, FALSE, $prefix);
		if ($keys !== NULL) {
			foreach ($keys as $key) {
				if ($key['type'] !== 'X509Certificate') {
					continue;
				}
				if ($key['signing'] !== TRUE) {
					continue;
				}
				$certData = $key['X509Certificate'];
				$pem = "-----BEGIN CERTIFICATE-----\n" .
					chunk_split($certData, 64) .
					"-----END CERTIFICATE-----\n";
				$certFingerprint = strtolower(sha1(base64_decode($certData)));

				return array(
					'certData' => $certData,
					'PEM' => $pem,
					'certFingerprint' => array($certFingerprint),
				);
			}
			/* No valid key found. */
		} elseif ($metadata->hasValue($prefix . 'certFingerprint')) {
			/* We only have a fingerprint available. */
			$fps = $metadata->getArrayizeString($prefix . 'certFingerprint');

			/* Normalize fingerprint(s) - lowercase and no colons. */
			foreach($fps as &$fp) {
				assert('is_string($fp)');
				$fp = strtolower(str_replace(':', '', $fp));
			}

			/* We can't build a full certificate from a fingerprint, and may as well
			 * return an array with only the fingerprint(s) immediately.
			 */
			return array('certFingerprint' => $fps);
		}

		/* No public key/certificate available. */
		if ($required) {
			throw new Exception('No public key / certificate found in metadata.');
		} else {
			return NULL;
		}
	}


	/**
	 * Load private key from metadata.
	 *
	 * This function loads a private key from a metadata array. It searches for the
	 * following elements:
	 * 'privatekey'  Name of a private key file in the cert-directory.
	 * 'privatekey_pass'  Password for the private key.
	 *
	 * It returns and array with the following elements:
	 * 'PEM'  Data for the private key, in PEM-format
	 * 'password'  Password for the private key.
	 *
	 * @param SimpleSAML_Configuration $metadata  The metadata array the private key should be loaded from.
	 * @param bool $required  Whether the private key is required. If this is TRUE, a
	 *                        missing key will cause an exception. Default is FALSE.
	 * @param string $prefix  The prefix which should be used when reading from the metadata
	 *                        array. Defaults to ''.
	 * @return array|NULL  Extracted private key, or NULL if no private key is present.
	 */
	public static function loadPrivateKey(SimpleSAML_Configuration $metadata, $required = FALSE, $prefix = '') {
		assert('is_bool($required)');
		assert('is_string($prefix)');

		$file = $metadata->getString($prefix . 'privatekey', NULL);
		if ($file === NULL) {
			/* No private key found. */
			if ($required) {
				throw new Exception('No private key found in metadata.');
			} else {
				return NULL;
			}
		}

		$file = SimpleSAML_Utilities::resolveCert($file);
		$data = @file_get_contents($file);
		if ($data === FALSE) {
			throw new Exception('Unable to load private key from file "' . $file . '"');
		}

		$ret = array(
			'PEM' => $data,
		);

		if ($metadata->hasValue($prefix . 'privatekey_pass')) {
			$ret['password'] = $metadata->getString($prefix . 'privatekey_pass');
		}

		return $ret;
	}


	/**
	 * Format a DOM element.
	 *
	 * This function takes in a DOM element, and inserts whitespace to make it more
	 * readable. Note that whitespace added previously will be removed.
	 *
	 * @param DOMElement $root  The root element which should be formatted.
	 * @param string $indentBase  The indentation this element should be assumed to
	 *                         have. Default is an empty string.
	 */
	public static function formatDOMElement(DOMElement $root, $indentBase = '') {
		assert(is_string($indentBase));

		/* Check what this element contains. */
		$fullText = ''; /* All text in this element. */
		$textNodes = array(); /* Text nodes which should be deleted. */
		$childNodes = array(); /* Other child nodes. */
		for ($i = 0; $i < $root->childNodes->length; $i++) {
			$child = $root->childNodes->item($i);

			if($child instanceof DOMText) {
				$textNodes[] = $child;
				$fullText .= $child->wholeText;

			} elseif ($child instanceof DOMComment || $child instanceof DOMElement) {
				$childNodes[] = $child;

			} else {
				/* Unknown node type. We don't know how to format this. */
				return;
			}
		}

		$fullText = trim($fullText);
		if (strlen($fullText) > 0) {
			/* We contain text. */
			$hasText = TRUE;
		} else {
			$hasText = FALSE;
		}

		$hasChildNode = (count($childNodes) > 0);

		if ($hasText && $hasChildNode) {
			/* Element contains both text and child nodes - we don't know how to format this one. */
			return;
		}

		/* Remove text nodes. */
		foreach ($textNodes as $node) {
			$root->removeChild($node);
		}

		if ($hasText) {
			/* Only text - add a single text node to the element with the full text. */
			$root->appendChild(new DOMText($fullText));
			return;

		}

		if (!$hasChildNode) {
			/* Empty node. Nothing to do. */
			return;
		}

		/* Element contains only child nodes - add indentation before each one, and
		 * format child elements.
		 */
		$childIndentation = $indentBase . '  ';
		foreach ($childNodes as $node) {
			/* Add indentation before node. */
			$root->insertBefore(new DOMText("\n" . $childIndentation), $node);

			/* Format child elements. */
			if ($node instanceof DOMElement) {
				self::formatDOMElement($node, $childIndentation);
			}
		}

		/* Add indentation before closing tag. */
		$root->appendChild(new DOMText("\n" . $indentBase));
	}


	/**
	 * Format an XML string.
	 *
	 * This function formats an XML string using the formatDOMElement function.
	 *
	 * @param string $xml  XML string which should be formatted.
	 * @param string $indentBase  Optional indentation which should be applied to all
	 *                            the output. Optional, defaults to ''.
	 * @return string  Formatted string.
	 */
	public static function formatXMLString($xml, $indentBase = '') {
		assert('is_string($xml)');
		assert('is_string($indentBase)');

		$doc = new DOMDocument();
		if (!$doc->loadXML($xml)) {
			throw new Exception('Error parsing XML string.');
		}

		$root = $doc->firstChild;
		self::formatDOMElement($root);

		return $doc->saveXML($root);
	}

	/*
	 * Input is single value or array, returns an array.
	 */
	public static function arrayize($data, $index = 0) {
		if (is_array($data)) {
			return $data;
		} else {
			return array($index => $data);
		}
	}


	/**
	 * Check whether the current user is a admin user.
	 *
	 * @return bool  TRUE if the current user is a admin user, FALSE if not.
	 */
	public static function isAdmin() {

		$session = SimpleSAML_Session::getInstance();

		return $session->isValid('admin') || $session->isValid('login-admin');
	}


	/**
	 * Retrieve a admin login URL.
	 *
	 * @param string|NULL $returnTo  The URL the user should arrive on after admin authentication.
	 * @return string  A URL which can be used for admin authentication.
	 */
	public static function getAdminLoginURL($returnTo = NULL) {
		assert('is_string($returnTo) || is_null($returnTo)');

		if ($returnTo === NULL) {
			$returnTo = SimpleSAML_Utilities::selfURL();
		}

		return SimpleSAML_Module::getModuleURL('core/login-admin.php', array('ReturnTo' => $returnTo));
	}






	/**
	 * Create a link which will POST data to HTTP in a secure way.
	 *
	 * @param string $destination  The destination URL.
	 * @param array $post  The name-value pairs which will be posted to the destination.
	 * @return string  A URL which can be accessed to post the data.
	 */
	public static function createHttpPostRedirectLink($destination, $post) {
		assert('is_string($destination)');
		assert('is_array($post)');

		$postId = SimpleSAML_Utilities::generateID();
		$postData = array(
			'post' => $post,
			'url' => $destination,
		);

		$session = SimpleSAML_Session::getInstance();
		$session->setData('core_postdatalink', $postId, $postData);

		$redirInfo = base64_encode(self::aesEncrypt($session->getSessionId() . ':' . $postId));

		$url = SimpleSAML_Module::getModuleURL('core/postredirect.php', array('RedirInfo' => $redirInfo));
		$url = preg_replace("#^https:#", "http:", $url);

		return $url;
	}


	/**
	 * Validate a certificate against a CA file, by using the builtin
	 * openssl_x509_checkpurpose function
	 *
	 * @param string $certificate  The certificate, in PEM format.
	 * @param string $caFile  File with trusted certificates, in PEM-format.
	 * @return boolean|string TRUE on success, or a string with error messages if it failed.
	 */
	private static function validateCABuiltIn($certificate, $caFile) {
		assert('is_string($certificate)');
		assert('is_string($caFile)');

		/* Clear openssl errors. */
		while(openssl_error_string() !== FALSE);

		$res = openssl_x509_checkpurpose($certificate, X509_PURPOSE_ANY, array($caFile));

		$errors = '';
		/* Log errors. */
		while( ($error = openssl_error_string()) !== FALSE) {
			$errors .= ' [' . $error . ']';
		}

		if($res !== TRUE) {
			return $errors;
		}

		return TRUE;
	}


	/**
	 * Validate the certificate used to sign the XML against a CA file, by using the "openssl verify" command.
	 *
	 * This function uses the openssl verify command to verify a certificate, to work around limitations
	 * on the openssl_x509_checkpurpose function. That function will not work on certificates without a purpose
	 * set.
	 *
	 * @param string $certificate  The certificate, in PEM format.
	 * @param string $caFile  File with trusted certificates, in PEM-format.
	 * @return boolean|string TRUE on success, a string with error messages on failure.
	 */
	private static function validateCAExec($certificate, $caFile) {
		assert('is_string($certificate)');
		assert('is_string($caFile)');

		$command = array(
			'openssl', 'verify',
			'-CAfile', $caFile,
			'-purpose', 'any',
			);

		$cmdline = '';
		foreach($command as $c) {
			$cmdline .= escapeshellarg($c) . ' ';
		}

		$cmdline .= '2>&1';
		$descSpec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			);
		$process = proc_open($cmdline, $descSpec, $pipes);
		if (!is_resource($process)) {
			throw new Exception('Failed to execute verification command: ' . $cmdline);
		}

		if (fwrite($pipes[0], $certificate) === FALSE) {
			throw new Exception('Failed to write certificate for verification.');
		}
		fclose($pipes[0]);

		$out = '';
		while (!feof($pipes[1])) {
			$line = trim(fgets($pipes[1]));
			if(strlen($line) > 0) {
				$out .= ' [' . $line . ']';
			}
		}
		fclose($pipes[1]);

		$status = proc_close($process);
		if ($status !== 0 || $out !== ' [stdin: OK]') {
			return $out;
		}

		return TRUE;
	}


	/**
	 * Validate the certificate used to sign the XML against a CA file.
	 *
	 * This function throws an exception if unable to validate against the given CA file.
	 *
	 * @param string $certificate  The certificate, in PEM format.
	 * @param string $caFile  File with trusted certificates, in PEM-format.
	 */
	public static function validateCA($certificate, $caFile) {
		assert('is_string($certificate)');
		assert('is_string($caFile)');

		if (!file_exists($caFile)) {
			throw new Exception('Could not load CA file: ' . $caFile);
		}

		SimpleSAML_Logger::debug('Validating certificate against CA file: ' . var_export($caFile, TRUE));

		$resBuiltin = self::validateCABuiltIn($certificate, $caFile);
		if ($resBuiltin !== TRUE) {
			SimpleSAML_Logger::debug('Failed to validate with internal function: ' . var_export($resBuiltin, TRUE));

			$resExternal = self::validateCAExec($certificate, $caFile);
			if ($resExternal !== TRUE) {
				SimpleSAML_Logger::debug('Failed to validate with external function: ' . var_export($resExternal, TRUE));
				throw new Exception('Could not verify certificate against CA file "'
					. $caFile . '". Internal result:' . $resBuiltin .
					' External result:' . $resExternal);
			}
		}

		SimpleSAML_Logger::debug('Successfully validated certificate.');
	}


	/**
	 * Atomically write a file.
	 *
	 * This is a helper function for safely writing file data atomically.
	 * It does this by writing the file data to a temporary file, and then
	 * renaming this to the correct name.
	 *
	 * @param string $filename  The name of the file.
	 * @param string $data  The data we should write to the file.
	 */
	public static function writeFile($filename, $data, $mode=0600) {
		assert('is_string($filename)');
		assert('is_string($data)');
		assert('is_numeric($mode)');

		$tmpFile = $filename . '.new.' . getmypid() . '.' . php_uname('n');

		$res = file_put_contents($tmpFile, $data);
		if ($res === FALSE) {
			throw new SimpleSAML_Error_Exception('Error saving file ' . $tmpFile .
				': ' . SimpleSAML_Utilities::getLastError());
		}

		if (!self::isWindowsOS()) {
			$res = chmod($tmpFile, $mode);
			if ($res === FALSE) {
				unlink($tmpFile);
				throw new SimpleSAML_Error_Exception('Error changing file mode ' . $tmpFile .
					': ' . SimpleSAML_Utilities::getLastError());
			}
		}

		$res = rename($tmpFile, $filename);
		if ($res === FALSE) {
			unlink($tmpFile);
			throw new SimpleSAML_Error_Exception('Error renaming ' . $tmpFile . ' to ' .
				$filename . ': ' . SimpleSAML_Utilities::getLastError());
		}
	}


	/**
	 * Disable reporting of the given log levels.
	 *
	 * Every call to this function must be followed by a call to popErrorMask();
	 *
	 * @param int $mask  The log levels that should be masked.
	 */
	public static function maskErrors($mask) {
		assert('is_int($mask)');

		$currentEnabled = error_reporting();
		self::$logLevelStack[] = array($currentEnabled, self::$logMask);

		$currentEnabled &= ~$mask;
		error_reporting($currentEnabled);
		self::$logMask |= $mask;
	}


	/**
	 * Pop an error mask.
	 *
	 * This function restores the previous error mask.
	 */
	public static function popErrorMask() {

		$lastMask = array_pop(self::$logLevelStack);
		error_reporting($lastMask[0]);
		self::$logMask = $lastMask[1];
	}


	/**
	 * Find the default endpoint in an endpoint array.
	 *
	 * @param array $endpoints  Array with endpoints.
	 * @param array $bindings  Array with acceptable bindings. Can be NULL if any binding is allowed.
	 * @return  array|NULL  The default endpoint, or NULL if no acceptable endpoints are used.
	 */
	public static function getDefaultEndpoint(array $endpoints, array $bindings = NULL) {

		$firstNotFalse = NULL;
		$firstAllowed = NULL;

		/* Look through the endpoint list for acceptable endpoints. */
		foreach ($endpoints as $i => $ep) {
			if ($bindings !== NULL && !in_array($ep['Binding'], $bindings, TRUE)) {
				/* Unsupported binding. Skip it. */
				continue;
			}

			if (array_key_exists('isDefault', $ep)) {
				if ($ep['isDefault'] === TRUE) {
					/* This is the first endpoitn with isDefault set to TRUE. */
					return $ep;
				}
				/* isDefault is set to FALSE, but the endpoint is still useable as a last resort. */
				if ($firstAllowed === NULL) {
					/* This is the first endpoint that we can use. */
					$firstAllowed = $ep;
				}
			} else {
				if ($firstNotFalse === NULL) {
					/* This is the first endpoint without isDefault set. */
					$firstNotFalse = $ep;
				}
			}
		}

		if ($firstNotFalse !== NULL) {
			/* We have an endpoint without isDefault set to FALSE. */
			return $firstNotFalse;
		}

		/*
		 * $firstAllowed either contains the first endpoint we can use, or it
		 * contains NULL if we cannot use any of the endpoints. Either way we
		 * return the value of it.
		 */
		return $firstAllowed;
	}


	/**
	 * Check for session cookie, and show missing-cookie page if it is missing.
	 *
	 * @param string|NULL $retryURL  The URL the user should access to retry the operation.
	 */
	public static function checkCookie($retryURL = NULL) {
		assert('is_string($retryURL) || is_null($retryURL)');

		$session = SimpleSAML_Session::getInstance();
		if ($session->hasSessionCookie()) {
			return;
		}

		/* We didn't have a session cookie. Redirect to the no-cookie page. */

		$url = SimpleSAML_Module::getModuleURL('core/no_cookie.php');
		if ($retryURL !== NULL) {
			$url = self::addURLParameter($url, array('retryURL' => $retryURL));
		}
		self::redirectTrustedURL($url);
	}



	/**
	 * Helper function to retrieve a file or URL with proxy support.
	 *
	 * An exception will be thrown if we are unable to retrieve the data.
	 *
	 * @param string $path  The path or URL we should fetch.
	 * @param array $context  Extra context options. This parameter is optional.
	 * @param boolean $getHeaders Whether to also return response headers. Optional.
	 * @return mixed array if $getHeaders is set, string otherwise
	 */
	public static function fetch($path, $context = array(), $getHeaders = FALSE) {
		assert('is_string($path)');

		$proxy = null;
		if ($proxy !== NULL) {
			if (!isset($context['http']['proxy'])) {
				$context['http']['proxy'] = $proxy;
			}
			if (!isset($context['http']['request_fulluri'])) {
				$context['http']['request_fulluri'] = TRUE;
			}
			// If the remote endpoint over HTTPS uses the SNI extension
			// (Server Name Indication RFC 4366), the proxy could
			// introduce a mismatch between the names in the
			// Host: HTTP header and the SNI_server_name in TLS
			// negotiation (thanks to Cristiano Valli @ GARR-IDEM
			// to have pointed this problem).
			// See: https://bugs.php.net/bug.php?id=63519
			// These controls will force the same value for both fields.
			// Marco Ferrante (marco@csita.unige.it), Nov 2012
			if (preg_match('#^https#i', $path)
				&& defined('OPENSSL_TLSEXT_SERVER_NAME')
				&& OPENSSL_TLSEXT_SERVER_NAME) {
				// Extract the hostname
				$hostname = parse_url($path, PHP_URL_HOST);
				if (!empty($hostname)) {
					$context['ssl'] = array(
						'SNI_server_name' => $hostname,
						'SNI_enabled' => TRUE,
						);
				}
				else {
					SimpleSAML_Logger::warning('Invalid URL format or local URL used through a proxy');
				}
			}
		}

		$context = stream_context_create($context);

		$data = file_get_contents($path, FALSE, $context);
		if ($data === FALSE) {
			throw new Exception('Error fetching ' . var_export($path, TRUE) . ':' . self::getLastError());
		}

		// Data and headers.
		if ($getHeaders) {

			if (isset($http_response_header)) {
				$headers = array();
				foreach($http_response_header as $h) {
					if(preg_match('@^HTTP/1\.[01]\s+\d{3}\s+@', $h)) {
						$headers = array(); // reset
						$headers[0] = $h;
						continue;
					}
					$bits = explode(':', $h, 2);
					if(count($bits) === 2) {
						$headers[strtolower($bits[0])] = trim($bits[1]);
					}
				}
			} else {
				/* No HTTP headers - probably a different protocol, e.g. file. */
				$headers = NULL;
			}

			return array($data, $headers);
		}

		return $data;
	}


	/**
	 * Function to AES encrypt data.
	 *
	 * @param string $clear  Data to encrypt.
	 * @return array  The encrypted data and IV.
	 */
	public static function aesEncrypt($clear) {
		assert('is_string($clear)');

		if (!function_exists("mcrypt_encrypt")) {
			throw new Exception("aesEncrypt needs mcrypt php module.");
		}

		$enc = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_CBC;

		$blockSize = mcrypt_get_block_size($enc, $mode);
		$ivSize = mcrypt_get_iv_size($enc, $mode);
		$keySize = mcrypt_get_key_size($enc, $mode);

		$key = hash('sha256', self::getSecretSalt(), TRUE);
		$key = substr($key, 0, $keySize);

		$len = strlen($clear);
		$numpad = $blockSize - ($len % $blockSize);
		$clear = str_pad($clear, $len + $numpad, chr($numpad));

		$iv = self::generateRandomBytes($ivSize);

		$data = mcrypt_encrypt($enc, $key, $clear, $mode, $iv);

		return $iv . $data;
	}


	/**
	 * Function to AES decrypt data.
	 *
	 * @param $data  Encrypted data.
	 * @param $iv  IV of encrypted data.
	 * @return string  The decrypted data.
	 */
	public static function aesDecrypt($encData) {
		assert('is_string($encData)');

		if (!function_exists("mcrypt_encrypt")) {
			throw new Exception("aesDecrypt needs mcrypt php module.");
		}

		$enc = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_CBC;

		$ivSize = mcrypt_get_iv_size($enc, $mode);
		$keySize = mcrypt_get_key_size($enc, $mode);

		$key = hash('sha256', self::getSecretSalt(), TRUE);
		$key = substr($key, 0, $keySize);

		$iv = substr($encData, 0, $ivSize);
		$data = substr($encData, $ivSize);

		$clear = mcrypt_decrypt($enc, $key, $data, $mode, $iv);

		$len = strlen($clear);
		$numpad = ord($clear[$len - 1]);
		$clear = substr($clear, 0, $len - $numpad);

		return $clear;
	}


	/**
	 * This function checks if we are running on Windows OS.
	 *
	 * @return TRUE if we are on Windows OS, FALSE otherwise.
	 */
	public static function isWindowsOS() {
		return substr(strtoupper(PHP_OS),0,3) == 'WIN';
	}


	/**
	 * Set a cookie.
	 *
	 * @param string $name  The name of the session cookie.
	 * @param string|NULL $value  The value of the cookie. Set to NULL to delete the cookie.
	 * @param array|NULL $params  Cookie parameters.
	 * @param bool $throw  Whether to throw exception if setcookie fails.
	 */
	public static function setCookie($name, $value, array $params = NULL, $throw = TRUE) {
		assert('is_string($name)');
		assert('is_string($value) || is_null($value)');

		$default_params = array(
			'lifetime' => 0,
			'expire' => NULL,
			'path' => '/',
			'domain' => NULL,
			'secure' => FALSE,
			'httponly' => TRUE,
			'raw' => FALSE,
		);

		if ($params !== NULL) {
			$params = array_merge($default_params, $params);
		} else {
			$params = $default_params;
		}

		// Do not set secure cookie if not on HTTPS
		if ($params['secure'] && !self::isHTTPS()) {
			SimpleSAML_Logger::warning('Setting secure cookie on http not allowed.');
			return;
		}

		if ($value === NULL) {
			$expire = time() - 365*24*60*60;
		} elseif (isset($params['expire'])) {
			$expire = $params['expire'];
		} elseif ($params['lifetime'] === 0) {
			$expire = 0;
		} else {
			$expire = time() + $params['lifetime'];
		}

		if ($params['raw']) {
			$success = setrawcookie($name, $value, $expire, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		} else {
			$success = setcookie($name, $value, $expire, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}

		if (!$success) {
			if ($throw) {
				throw new SimpleSAML_Error_Exception('Error setting cookie - headers already sent.');
			} else {
				SimpleSAML_Logger::warning('Error setting cookie - headers already sent.');
			}
		}
	}

}