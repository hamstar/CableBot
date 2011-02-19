<?php

/**
 * Gets cables from LeakFeed and returns them in a Cable object
 * or array of cable ids
 * @author Robert McLeod
 * @since 2010
 * @version 0.7.1
 */

class CableSpider {
    
	function __construct() {
            $this->c = new Curl();
            $this->c->useragent = "CableSpider 0.7.1 / http://cablewiki.net/index.php?title=User:CableBot";
	}

	/**
	 * Gets the latest 50 cables from LeakFeed and returns an array of id's
	 * Would return the cables themselves however leakfeed doesn't parse the
	 * tags unless its on a single cable
	 *
	 * @return array of cable ids
	 */
	public function getLatestCables() {
		
            $json = $this->c->get( FEED_LATEST_CABLES )->body;

            $json_cables = json_decode( $json );

            $ids = array();

            foreach ( $json_cables as $cable ) {
                $ids[] = $cable->identifier;
            }

            return $ids;

	}

	/**
	 * Gets a single cable from the feed
	 * @param int $id
	 * @return Cable object
	 */
	public function getCable( $id ) {

            $json = $this->c->get( FEED_SINGLE_CABLE . $id . '.' . FEED_FORMAT )->body;

            return new Cable( $json );
	
	}

    /**
     * Returns the json text of the cable
     *
     * @param string $id the cable id
     * @return string json string of the cable
     */
    public function getJson( $id ) {
        return $this->c->get( FEED_SINGLE_CABLE . $id . '.' . FEED_FORMAT )->body;
    }


}