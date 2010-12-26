<?php

include 'config.php';
include 'CableBot.php';
include 'curl.php';
include 'Logger.php';

$bot = new CableBot;

// Check if we are just testing
if ( isset($_GET['test']) ) {
    $bot->setTest( true );
}

// Check what we need to do
if ( isset($_GET['id']) ) { // id is given
    if ( strstr( $_GET['id'], '|') ) { // multiple ids
	$bot->addCablesFromArray( explode('|', $_GET['id']) );
    } else { // single id
	$bot->addSingleCable( $_GET['id'] );
    }
} elseif ( isset( $_GET['latest'] ) ) { // latest cables only
    $bot->addLatestCables();
}

echo '<pre>',nl2br(file_get_contents(DEBUG_LOG_FILE)),'</pre>';