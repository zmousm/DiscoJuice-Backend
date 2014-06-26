<?php

class FeedBuilder {

	protected $items;

	function __construct() {
		$this->items = array();
	}

	public static function slim($str) {
		return preg_replace('/ /', '', $str);
	}



	protected function find($key, $value) {
		foreach($this->items AS $index => $item) {
			if (isset($item[$key]) && $item[$key] === $value) return $index;
		}
		return null;
	}


	protected function add($item, $criteria = null, $include = false) {

		if ($criteria === null) {
			$this->items[] = $item;
			return;
		}

		if (isset($item[$criteria])) {

			$search = $this->find($criteria, $item[$criteria]);

			if ($search !== null) {
				$this->items[$search] = array_merge($this->items[$search], $item);
				return;
			}

		}




		if ($include) {
			$this->items[] = $item;
			return;
		}

	}


	public function debug() {
		$i = 0; 
		echo "debug\n";
		foreach($this->items AS $item) {
			// if (++$i > 15) { break; }
			echo "item " . $i . "\n"; 
			print_r($item); 
			echo "\n";
		}
	}

}