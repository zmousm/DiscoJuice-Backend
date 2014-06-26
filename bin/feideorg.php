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



$backend = new FeideBackend();
if ($command['cache-only']) {
	DiscoUtils::log("Running in cache-only mode");
	$backend->enableCacheOnly(true);	
}

DiscoUtils::log("DiscoJuice update script. Now updating feide feed.", true);
$backend->update();	
