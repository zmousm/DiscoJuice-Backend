<?php

class FeideHelper extends FeedBuilder {

	protected $feideconfig;
	protected $kommuner, $fylker;

	function __construct($c) {


		parent::__construct();


		$this->feideconfig = $c;
		

		$this->fetchOrgInfo();
		$this->getKind();
		$this->addKNR();

		$this->addKommuner();

		// $this->kommuner = array();
		// $this->fylker = array();

		// $this->loadKommuneListe();
		// $this->loadFylkeListe();


		foreach($this->items AS $i) {
			// print_r($i);
			if (isset($i['id'])) {
				$item = FeideOrg::fromDB($i);
				$item->save();

			}

		}

		// echo json_encode($this->items, JSON_PRETTY_PRINT);
		// $this->debug(); 
	}



	function getKind() {
		$kind = new KIND($this->feideconfig['kind.db']);
		$items = $kind->getKIND();
		foreach($items AS $i) {

			// print_r($i); exit;
			$this->add($i, 'id');
		}

	}

	function addKommuner() {
		$cacheFile = dirname(dirname(dirname(__FILE__))) . '/tmp/kommuner.json';
		$kommuner = json_decode(file_get_contents($cacheFile), true);

		foreach($kommuner AS $kommune) {
			$this->add($kommune, 'knr', true);
		}

	}

	function addKNR() {
		
		// http://hotell.difi.no/?dataset=difi/etatsbasen/organization

		$url = 'http://hotell.difi.no/api/json/difi/etatsbasen/organization?orgstructid=66';
		$data = $this->getPaginatedAPI($url);

		// print_r($data); exit;

		foreach($data AS $entry) {
			$title = array(
				'en' => $entry['name_en'],
				'nb' => $entry['name_nb'],
				'nn' => $entry['name_nn'],
			);
			$new = array(
				'title' => $title,
				'knr' => $entry['kommunenummer'], 
				'orgnr' => $entry['orgid']
			);
			// print_r($new);
			$this->add($new, 'orgnr');
		}

	}


	function addKNR2() {
		$mapping = array(
			'954597482' => '0624',
		);

		// 1818 Herøy kommune nordland
		// 1444",
            // "name_en": "Hornindal Municipality
            // 
            //             "kommunenummer": "1418",
            // "name_en": "Balestrand Municipality",
            // 
            //             "kommunenummer": "2015",
            // "name_en": "Hasvik Municipality",
            //             "kommunenummer": "2027",
            // "name_en": "Nesseby Municipality",
            //           "kommunenummer": "1439",
            // "name_en": "V\u00e5gs\u00f8y Municipality",
            // 
                        // "kommunenummer": "1426",
                        // 
            // "name_en": "Luster Municipality",
        // "kommunenummer": "1422",
        //     "name_en": "L\u00e6rdal Municipality",


		foreach($mapping AS $orgnr => $knr) {
			$new = array('knr' => $knr, 'orgnr' => $orgnr);
			$this->add($new, 'orgnr');
		}

	}



	function fillKommune() {



	}

	function fillFylke() {

	}


	function getPaginatedAPI($url) {

		$data = array();
		$more = true;
		$page = 1;
		while($more) {
			$curpage = json_decode(file_get_contents($url . '&page=' . $page), true);
			$data = array_merge($data, $curpage['entries']);
			if ($page >= $curpage['pages']) {
				$more = false;
			}
			$page++;
		}

		return $data;


	}

	function loadFylkeListe() {

		$fylkeURL = 'http://hotell.difi.no/api/json/difi/geo/fylke';
		$data = $this->getPaginatedAPI($fylkeURL);

		foreach($data AS $item) {
			$this->fylker[$item['nummer']] = $item['navn'];
		}


	}

	function loadKommuneListe() {

		$kommunerURL = 'http://hotell.difi.no/api/json/difi/geo/kommune';
		$data = $this->getPaginatedAPI($kommunerURL);

		foreach($data AS $item) {
			$n = array(
				'fylkenr' => $item['fylke'],
				'fylkenr' => $item['fylke'],
				'kort' => $item['navn'],
				'navn' => $item['navn'] . ' kommune',
			);
		}

		// print_r($this->kommuner); exit;
	}


	function fetchOrgInfo() {

		// Will always fetch from cache...
		$cachefile = '/tmp/feide-orginfo.json';
		if (file_exists($cachefile)) {

			$orginfo = json_decode(file_get_contents($cachefile), true);

		} else {

			$url = 'https://api.feide.no/orginfo/0/all?type=home_organization';
			$data = file_get_contents($url);
			file_put_contents($cachefile, $data);

			$orginfo = json_decode($data, true);

		}

		$res = array();

		foreach($orginfo AS $k => $item) {

			$n = $item;
			if (isset($item['name'])) {
				$n['title'] = $item['name'];
				unset($n['name']);
			}
			// if (isset($item['id'])) {
			// 	$n['kind.id'] = $item['id'];
			// 	unset($n['id']);
			// }
			$res[] = $n;
			// echo "Item " . $n['title']['nb']. "\n";
			// print_r($n); exit;
			// if (isset($n['id'])) {
				$this->add($n);	
			// }
			
		}

		return $res;


	}


}