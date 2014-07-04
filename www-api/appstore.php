<?php

/*
 * This is the DiscoJuice API
 *
 */

require_once(dirname(dirname(__FILE__)) . '/lib/autoload.php');

class NotFound extends Exception {

}

if (strtolower($_SERVER['REQUEST_METHOD']) === 'options') {
	header('Content-Type: application/json; charset=utf-8');
	exit;
} 


header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: HEAD, GET, OPTIONS, POST, DELETE, PATCH");
header("Access-Control-Allow-Headers: Authorization, X-Requested-With, Origin, Accept, Content-Type");
header("Access-Control-Expose-Headers: Authorization, X-Requested-With, Origin, Accept, Content-Type");


$profiling = microtime(true);
error_log("Time START    :     ======> " . (microtime(true) - $profiling));

function ss($a, $b) {
	$a1 = $a->get('statistics', 0);
	$b1 = $b->get('statistics', 0);
	// echo "Compare " . $a1 . " with " . $b1; exit;
	if ($a1 === $b1) return 0;
	return ($a1 < $b1) ? 1 : -1;
}

DiscoUtils::logConsole(false);

try {


	$apiconfig = json_decode(file_get_contents(dirname($BASE) . '/etc/apiconfig.json'), TRUE);

	$response = null;

	$parameters = null;
	$body = null;

	if (DiscoUtils::route('options', '.*', $parameters)) {
		header('Content-Type: application/json; charset=utf-8');
		exit;
	}

	$store = new DiscoStore();
	$logocache = new LogoCache();



	if (DiscoUtils::route('get', '^/logo/([^/]+)$', $parameters, $qs)) {

		// echo "We found logo " . $parameters[0];
		$logostore = new LogoStore('feide');

		$id = $parameters[1];

		// if (!isset($_REQUEST['entityId'])) {
		// 	throw new Exception('Missing required parameter entityId');
		// }
		// if (!isset($_REQUEST['feed'])) {
		// 	throw new Exception('Missing required parameter feed');
		// }

		$data = $logostore->get($id, true);
		// $data = $logostore->get('https://pieter.aai.surfnet.nl/simplesamlphp/saml2/idp/metadata.php', 'surfnet2', true);

		// print_r($data);

		header('Content-Type: image/png');
		echo $data['logo']->bin;
		exit;



	} 




	$gk = new GateKeeperController($apiconfig['token']);
	$gk->requireToken()->requireUser();

	if (DiscoUtils::route('get', '^/apps$', $parameters, $body)) {


	
		$services = FeideService::getAll();
		usort($services, 'ss');
		$col = new Collection($services);
		$response = $col->getView(array('realm' => 'uninett.no'));


	} else if (DiscoUtils::route('get', '^/apps/([^/]+)/([^/]+)$', $parameters, $qs)) {


		$realm = $parameters[1];
		$type = $parameters[2];

		$services = FeideService::getAll();
		usort($services, 'ss');
		$col = new ServiceCollection($services);

		if ($type === 'available') {
			$col->filterByRealm($realm, true);
		} else if ($type === 'all') {

		} else if ($type === 'favs') {

			$userid = $gk->getUserID();
			$fav = Favourites::getByID($userid);
			$col->filterByList($fav->get('favs'));

		} else if ($type === 'potential') {
			$col->filterByRealm($realm, false);
			$col->filterByTarget('other', true);
		}



		$response = $col->getView(array('realm' => $realm));
		$response['orgInfo'] = FeideOrg::getByRealm($realm);




	} else if (DiscoUtils::route('get', '^/reviews/([^/]+)$', $parameters, $qs)) {


	} else if (DiscoUtils::route('post', '^/reviews/([^/]+)$', $parameters, $qs)) {


	} else if (DiscoUtils::route('get', '^/favs$', $parameters, $qs)) {

		$userid = $gk->getUserID();
		$fav = Favourites::getByID($userid);

		$data = $fav->getView();
		if (!empty($data) && isset($data['favs'])) {
			$response = $data['favs'];	
		} else {
			$response = array();
		}


	} else if (DiscoUtils::route('post', '^/favs$', $parameters, $qs)) {

		// $inputraw = file_get_contents("php://input");
		$userid = $gk->getUserID();
		// $response = array(
		// 	'userid' => $userid,
		// 	'data' => $inputraw,
		// 	'qs' => $qs,
		// 	'parameters' => $parameters,
		// 	'headers' => getallheaders(),
		// );


		$fav = new Favourites(array(
			'id' => $userid,
			'favs' => $qs,
		));
		$fav->save();


		$fav = Favourites::getByID($userid);
		$data = $fav->getView();
		if (!empty($data) && isset($data['favs'])) {
			$response = $data['favs'];	
		} else {
			$response = array();
		}



	} else {


		throw new Exception('Invalid request');
	}

	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response, JSON_PRETTY_PRINT);

	// $profiling = microtime(true);
	// $key = Utils::getPathString();
	$timer = round((microtime(true) - $profiling) * 1000.0);
	error_log("Time to run command:   ======> " . $timer . " ms");

	// UWAPLogger::stat('timing', $key, $timer);

// } catch(NotFound $e) {

// 	header("HTTP/1.0 404 Not Found");
// 	header('Content-Type: text/plain; charset: utf-8');
// 	echo "Error stack trace: \n";
// 	print_r($e);

} catch(GateKeeperException $e) {


	header("HTTP/1.0 401 Unauthorized");
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(
		array(
			"message" => $e->getMessage()
		)
	);
	exit;

} catch(Exception $e) {

	// TODO: Catch OAuth token expiration etc.! return correct error code.


	header("HTTP/1.0 500 Internal Server Error");
	header('Content-Type: text/plain; charset: utf-8');
	echo "Error stack trace: \n";
	print_r($e);


}


