#!/usr/bin/env php
<?php

// require(dirname(dirname(__FILE__)) . '/lib/autoload.php');


$links = array(
	'https://dlib-adidp.ucs.ed.ac.uk:442/Images/EdinaLogo110x58px.jpg',
	'https://idem.ced.inaf.it/LogoINAF100.png',
	'https://idp.dante.net/idp/images/80x60-DANTE.jpg',
	'https://idem.univpm.it/idp/shibboleth'
);


function url_get_contents ($Url) {
    if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,2); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function checkLink($url) {

	// $data = file_get_contents($url);
	$data = url_get_contents($url);
}

foreach($links AS $link) {

	echo "Process " . $link . "\n";
	checkLink($link);

}

