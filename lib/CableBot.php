<?php

/**
 * Manages the adding of cables to a wiki
 *
 * @author Robert McLeod
 * @since Febuary 2011
 * @version 0.1
 */
class CableBotManager {

    private $spider = null;
    private $saver = null;

    function __construct() {
        $this->spider = new CableSpider;
        $this->saver = new CableSaver;
    }

    /**
     * Save the IDs given to the wiki
     * or (without args) save the latest
     * cables to the wiki
     */
    function run( $ids = array() ) {
    
        // No ids so get the latest cables
        if ( empty($ids) ) {
            $ids = $this->spider->getLatestCables();
        }
        
        // Run through the ids gotten/given
        foreach ( $ids as $id ) {
            try {
                // try to save the cable to the wiki
                $cable = new Cable( $id );
                $this->saver->create( $cable );
            } catch ( Exception $e ) {
                Logger::log('Exception: ' .$e->getMessage() );
            }
        }
    
    }

}