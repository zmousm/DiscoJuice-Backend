<?php

/*
 * This is the DiscoJuice API
 *
 */

require_once(dirname(dirname(__FILE__)) . '/lib/autoload.php');

class NotFound extends Exception {

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

try {

	$response = null;

	$parameters = null;
	$body = null;

	if (DiscoUtils::route('options', '.*', $parameters)) {
		header('Content-Type: application/json; charset=utf-8');
		exit;
	}

	$store = new DiscoStore();
	$logostore = new DiscoStoreLogos();

	if (DiscoUtils::route('get', '^/apps$', $parameters, $body)) {


		$services = FeideService::getAll();
		$col = new Collection($services);
		$response = $col->getView();


	} else if (DiscoUtils::route('get', '^/apps/([^/]+)$', $parameters, $qs)) {


		$realm = $parameters[1];

		$services = FeideService::getByRealm($realm);
		$col = new Collection($services);
		$response = $col->getView();




	} else if (DiscoUtils::route('get', '^/logo/([^/]+)$', $parameters, $qs)) {

		echo "We found logo " . $parameters[0];


		$id = $parameters[1];

		// if (!isset($_REQUEST['entityId'])) {
		// 	throw new Exception('Missing required parameter entityId');
		// }
		// if (!isset($_REQUEST['feed'])) {
		// 	throw new Exception('Missing required parameter feed');
		// }

		$data = $logocache->get($id, true);
		// $data = $logostore->get('https://pieter.aai.surfnet.nl/simplesamlphp/saml2/idp/metadata.php', 'surfnet2', true);

		header('Content-Type: image/png');
		echo $data['logo']->bin;
		exit;


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

} catch(NotFound $e) {

	header("HTTP/1.0 404 Not Found");
	header('Content-Type: text/plain; charset: utf-8');
	echo "Error stack trace: \n";
	print_r($e);


} catch(Exception $e) {

	// TODO: Catch OAuth token expiration etc.! return correct error code.

	header("HTTP/1.0 500 Internal Server Error");
	header('Content-Type: text/plain; charset: utf-8');
	echo "Error stack trace: \n";
	print_r($e);


}
