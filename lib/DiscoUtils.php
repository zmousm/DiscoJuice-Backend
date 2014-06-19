<?php

class DiscoUtils {

	public static function log($txt, $head = false) {

		if ($head) {
			echo "\n";
			tcechon(date('F jS H:i:s') . "    " . $txt, 'cyan', 'bold', 'reverse');	
		} else {
			echo date('F jS H:i:s') . "    " . $txt . "\n";
		}

	}

	public static function error($txt) {

		tcechon(date('F jS H:i:s') . "    " . $txt, 'white', 'on_red');	

	}
	
	public static function debug($txt) {
		tcechon(date('F jS H:i:s') . "    " . $txt, 'white');	
	}

	public static function prefix($word, $prefix) {
		if ( strlen($word) < strlen($prefix)) {
				$tmp = $prefix;
				$prefix = $word;
				$word = $tmp;
		}
	
		$word = substr($word, 0, strlen($prefix));
	
		if ($prefix == $word) {
				return 1;
		}
	
		return 0;
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


}