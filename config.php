<?php

define('OVERWRITE', true );

// Wikimate config
define('WIKIMATE_USERNAME', 'BotName');
define('WIKIMATE_PASSWORD', 'secret');
define('WIKIMATE_API', 'http://example.com/api.php');
define('WIKIMATE_OVERWRITE', OVERWRITE );

// Feed config
define('FEED_FORMAT', 'json' );
define('FEED_LATEST_CABLES', 'http://api.leakfeed.com/v1/cables/latest.' . FEED_FORMAT);
define('FEED_SINGLE_CABLE', 'http://api.leakfeed.com/v1/cable/');

// File locations
define('COUNTRY_LIST', './resources/countries.txt');
define('TAGLIST', './resources/taglist.phps');
define('CABLE_TEMPLATE', file_get_contents('./templates/CableTemplate.txt') );
define('ANALYSIS_TEMPLATE', file_get_contents('./templates/AnalysisTemplate.txt') );

// Logging
define('DEBUG_LOGGING', true );
define('DEBUG_LOG_FILE', 'log-'.date('Y-m-d-Hm-s').'.txt');