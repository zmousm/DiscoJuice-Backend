#!/usr/bin/env php
<?php

require(dirname(dirname(__FILE__)) . '/lib/autoload.php');



$command = new Commando\Command();


$command->option()
    ->describedAs('Command to run: default is update.');

$command->option('cache-only')
	->boolean()
	->describedAs('Do not load metadata, only use existing cache.');

if ($command[0] === 'termcolor') {
	phpterm_demo();
	exit;
}



DiscoUtils::log("DiscoJuice update script. Now updating feide feed.", true);

$fav = Favourites::getByID('uuid:d665541c-2fe6-4843-8752-314587f4edd1');

if ($fav === null) {
	DiscoUtils::log("No favourites found");
	$fav = new Favourites(array(
		'id' => 'uuid:d665541c-2fe6-4843-8752-314587f4edd1',
		'favs' => array('foo1', 'foo2', 'bar3')
	));
	$fav->save();
} else {
	print_r($fav);


}

DiscoUtils::log("Done.");

