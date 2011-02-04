<?php

/**
 * Gets cables from LeakFeed and returns them in a Cable object
 * @author Robert McLeod
 * @since 2010
 * @version 0.6
 */

class CableBot {
    
	function __construct() {
            $this->c = new Curl();
            $this->c->useragent = "CableBot 0.6 / http://cablewiki.net/index.php?title=User:CableBot";
	}

	/**
	 * Gets the latest 50 cables from LeakFeed and returns them as an array of Cable objects
	 * @return array of Cables
	 */
	public function getLatestCables() {
		
            $json = $this->c->get( FEED_LATEST_CABLES )->body;

            $json_cables = json_decode( $json );

            foreach ( $json_cables as $cable ) {
                $cables[] = new Cable( json_encode( $cable ) );
            }

            return $cables;

	}

	/**
	 * Gets a single cable from the feed
	 * @param int $id
	 * @return Cable object
	 */
	public function getSingleCable( $id ) {

            $json = $this->c->get( FEED_SINGLE_CABLE . $id .'.json' )->body;

            return new Cable( $json );
	
	}


}