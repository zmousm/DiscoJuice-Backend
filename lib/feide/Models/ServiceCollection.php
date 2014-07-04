<?php


/**
* ServiceCOllection
*/
class ServiceCollection extends Collection {
	

	public function filterByRealm($realm, $include = true) {
		foreach($this->items AS $k => $v) {

			if ($v->isAllowed($realm) == $include) {

			} else {
				unset($this->items[$k]);
			}

		}
	} 

	public function filterByTarget($target, $include = true) {
		foreach($this->items AS $k => $v) {
			

			if ($v->hasTarget($target) == $include) {
				
			} else {
				unset($this->items[$k]);
			}

		}
	}

	public function filterByList($list) {


		foreach($this->items AS $k => $v) {
		
			if (in_array($v->get('id'), $list)) {

			} else {
				unset($this->items[$k]);
			}

		}

	}

	public function getView($opts = array()) {

		$data = parent::getView($opts);

		$providers = array();
		foreach($this->items AS $s) {
			$p = $s->get('provider', null);
			if ($p !== null) {
				if (!isset($providers[$p])) $providers[$p] = 0;
				$providers[$p]++;
			}
		}
		$data['Providers'] = $providers;
		return $data;
	}

}


