<?php

include 'config.php';
include 'CableBot.php';
include 'curl.php';
include 'Logger.php';

$bot = new CableBot;

if ( isset($_GET['id']) ) {
    $bot->setId( $_GET['id'] );
}

$bot->process();

echo '<pre>',nl2br(file_get_contents(DEBUG_LOG_FILE)),'</pre>';