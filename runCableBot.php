<?php

if ( !function_exists('php_sapi_name') ) {
    die("Sorry this won't work on your server because your PHP install doesn't have the php_sapi_name function built in");
}

include 'globals.php';

// Init our main classes
$spider = new CableSpider;
$saver = new CableSaver;

// Init our cables array
$cables = array();

// Get cables from command line or get args
if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) { // running from command line
    $args = $_SERVER['argv'];
    if ( count( $args ) == 1 && file_exists( $args[0] ) ) {
        $cables = explode( "\n", file_get_contents( $args[0] ) );
    } else {
        $cables = $args;
    }
} else { // running from browser
    if ( isset($_GET['id']) ) { // id is given
        if ( strstr( $_GET['id'], '|') ) { // multiple ids
            foreach ( explode('|', $_GET['id']) as $id ) {
                $cables[] = $id;
            }
        } else { // single id
            $cables[] = $_GET['id'];
        }
    } elseif ( isset( $_GET['latest'] ) ) { // latest cables only
        $cables = $spider->getLatestCables();
    }
}

// Do the actual cable importing
foreach ( $cables as $cable ) {
    try {
        $saver->create( $cable );
    } catch ( Exception $e ) {
        Logger::log('Caught Exception: '.$e->getMessage()."\n");
    }
}

// Drop the log contents
echo '<pre>',file_get_contents(DEBUG_LOG_FILE),'</pre>';