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



$backend = new FeideServices();
$backend->update();	
