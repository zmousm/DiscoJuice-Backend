<?php

use Goutte\Client;


/**
* Kommunehelper henter ut informerasjon om kommuner
*/
class KommuneHelper {
	
	protected $cacheFile;

	function __construct() {
		
		$this->cacheFile = dirname(dirname(dirname(__FILE__))) . '/tmp/kommuner.json';
		 
	}


	function update() {



		$client = new Client();


		$url = 'http://no.wikipedia.org/wiki/Norges_kommuner';
		$crawler = $client->request('GET', $url);


		$entries = array();
		$i = 0; 
		$crawler->filter('div#bodyContent table.wikitable tr')->each(function($node) use (&$i, &$entries) {

			if ($i > 0) {

				$ft = $node->filter('td')->eq(4)->html();
				$ft = preg_replace('/<(.*)>/', '', $ft);
				$ft = preg_replace('/[^0-9]/', '', $ft);

				$item = array();
				$item['knr'] = $node->filter('td')->eq(0)->text();
				$item['kommune.navn'] = $node->filter('td')->eq(1)->text();
				$item['kommune.admsenter'] = $node->filter('td')->eq(2)->text();
				$item['kommune.fylke'] = $node->filter('td')->eq(3)->text();
				// $item['kommune.folketall'] = urldecode($node->filter('td')->eq(4)->text());
				$item['kommune.folketall'] = intval($ft, 10);
				$item['kommune.areal'] = $node->filter('td')->eq(5)->text();
				$item['kommune.maalform'] = $node->filter('td')->eq(8)->text();

				$item['kommune.iconurl'] = 'http:' . $node->filter('td')->eq(7)
					->filter('img')->attr('src');

				// echo "Item " . $i . "\n";
				// print_r($item);
				$entries[] = $item;
			}
			$i++;
		} );

		file_put_contents($this->cacheFile, json_encode($entries));



	}
}
