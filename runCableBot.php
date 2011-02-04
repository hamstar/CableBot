<?php

include 'config.php';
include 'CableBot.php';
include 'curl.php';
include 'Logger.php';
include 'Wikimate.php';
include 'SaveCable.php';
include 'Cable.php';

$cablebot = new CableBot;
$savecable = new SaveCable;
$cables = array();
echo '<pre>';
// Check what we need to do
if ( isset($_GET['id']) ) { // id is given
    if ( strstr( $_GET['id'], '|') ) { // multiple ids
	foreach ( explode('|', $_GET['id']) as $id ) {
            $cables[] = $cablebot->getSingleCable( $id );
        }
    } else { // single id
	$cables[] = $cablebot->getSingleCable( $_GET['id'] );
    }
} elseif ( isset( $_GET['latest'] ) ) { // latest cables only
    array_merge( $cables, $cablebot->getLatestCables() );
}

foreach ( $cables as $cable ) {
    $savecable->create( $cable );
}
echo '</pre>';
echo '<pre>',nl2br(file_get_contents(DEBUG_LOG_FILE)),'</pre>';