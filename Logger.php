<?php
/**
 * Logger
 * @author Philip Chen
 * @since 2009
 */
class Logger {
	const TYPE_DEBUG = 1;
	const TYPE_PERFORMANCE = 2;
	
	private $_fd = null;
	private $_buffer = array();
	
	static public $canLog = true;
	static public $canEcho = false;
	
	static public function canLog($type) {
		if (!isset($GLOBALS['disableLogging']) && self::$canLog) {
			switch ($type) {
				case Logger::TYPE_DEBUG:
					if (DEBUG_LOGGING) {
						return true;							
					}
					return false;
				case Logger::TYPE_PERFORMANCE:
					if (PERFORMANCE_LOGGING) {
						return true;
					}
					return false;
			}
		}
		return false;
	}
	
	static public function log($msg="", $showCaller=false, $type=Logger::TYPE_DEBUG, $level=2) {
		if (Logger::canLog($type)) {		
			switch ($type) {
				case Logger::TYPE_DEBUG:
					Logger::logLine($msg, DEBUG_LOG_FILE, $showCaller, $level);					
					break;
				case Logger::TYPE_PERFORMANCE:
					Logger::logLine($msg, PERFORMANCE_LOG_FILE, $showCaller, $level);
					break;	
			}
		}
	}
	
	static public function logArray($msg, $array=null, $showCaller=false,  $type=Logger::TYPE_DEBUG) {
		if (Logger::canLog($type)) {
			if ($array === null) {
				return;
			}
			if ( is_object($msg) || is_array($msg) ) {
				$array = $msg;
				$msg = "";
			}			
			$msg = $msg . "\r\n" . var_export($array, true);
			Logger::log($msg, $type, $showCaller, 3);
		}		
	}
	
	static public function logSQL($msg, $sql, $vars=null, $showCaller=false, $type=Logger::TYPE_DEBUG) {
		if (Logger::canLog(($type))) {
			if ( $vars == null ) { // check if message specified
				$vars = $sql;
				$sql  = $msg;
				$msg  = "prepared sql:";
			}	
		//	Logger::log("sql: $sql");
			$sql = preg_replace('/\)/', ' ) ', $sql); 
			$sql = preg_replace('/\(/', ' ( ', $sql); 
			$sql = preg_replace('/,/', ' , ', $sql);
			if ( $vars != null )
			  Logger::logArray($vars); 
			foreach ( $vars as $name => $value ) {
		  	$newSql = "";
		  	$delimeters = " \n\t";
				$patterns = array();
				$word = strtok($sql, $delimeters);
				while ( $word !== false ) {
					$count = preg_match("/(.*):(\w+)([ \t\n,]*)/", $word, $patterns);
					if ( $count > 0 && $name == ":" . $patterns[2]) {
						
					  $delim = (is_numeric($value) ? "" : "'");
					  $newSql .= " $delim{$value}$delim";
					} else {
						$newSql .= " $word";	    
					}
						
					$word = strtok($delimeters);    
				}
				$sql = $newSql;
		  }
		  Logger::log("$msg: $sql", $type, $showCaller, 3);
		}
	}	
	
	static private function logLine($msg, $file, $showCaller, $level) {
		$prefix = date('Y-m-d H:m:s') . ' ';
		if ($showCaller) {
			$prefix .= Logger::getFileAndLineOfCaller($level);  
		}
		$prefix .= ': ';
		$msg .= "\r\n"; 
		$line = $prefix . $msg;
		if (self::$canEcho) echo $line . '<br />';
		file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
	}
	static private function getFileAndLineOfCaller($level = 1) {
	  if ( !function_exists("debug_backtrace") ) {
	    echo( "getFileAndLineOfCaller(): function debug_backtrace() does not exist<br>" );
	    return;
	  }
	  if ( $level == -1 ) {
	    $level = 99999;
	  }
	  $stackTrace = debug_backtrace();
	  $s = "";
	  $delim = "";
	  
	  $info = var_export($stackTrace, true);
	  if ( count($stackTrace) > 1 ) {
	        $i = $level;
	        $filePath = $stackTrace[$i]['file'];
	        $line = $stackTrace[$i]['line'];
	        $file = basename($filePath);
	        $class = "";
	        $n = $i+1;
	        if ( isset($stackTrace[$n]) ) {
	          if ( isset($stackTrace[$n]['class']) )
	            $class = $stackTrace[$n]['class'] . $stackTrace[$n]['type'];
	          $class .= $stackTrace[$n]['function'] . "()";
	        }
	        $s .= "{$delim}{$file}#{$line} {$class}";
	        $delim = "; ";
	  }
	  return $s;  
	}	
}
?>