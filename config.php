<?php

define('WIKI_USERNAME','BotName');
define('WIKI_PASSWORD','secret');
define('WIKI_API','http://example.com/api.php');

define('FEED_LATEST_CABLES','http://api.leakfeed.com/v1/cables/latest.json');
define('FEED_SINGLE_CABLE','http://api.leakfeed.com/v1/cable/');

define('COUNTRY_LIST','countries.txt');
define('TAGLIST','taglist.phps');

$logname = 'log-'.date('Y-m-d-Hm-s').'.txt';
define('DEBUG_LOG_FILE',$logname);

define('OVERWRITE', true);