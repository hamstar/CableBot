<?php

/**
 * Copies cables from LeakFeed to a mediawiki database
 * @author Robert McLeod
 * @since 2009
 */

class CableBot {

        private $id = false;
    
	function __construct() {
		$this->c = new Curl();
	}

	/**
	 * Sets the id to get if it is a singular id that is needed
	 * @param string $id The id of the cable
	 */
        public function setId( $id ) {
            $this->id = $id;
        }

	/**
	 * Logs into the wiki
	 */
	private function login() {
                Logger::log("Trying to login");

                $details = array(
			'action' => 'login',
			'lgname' => WIKI_USERNAME,
			'lgpassword' => WIKI_PASSWORD,
			'format' => 'json'
		);
		
		$loginResult = $this->c->post( WIKI_API, $details )->body;
		
		$loginResult = json_decode( $loginResult );
		Logger::log("Login result: ". print_r( $loginResult, true ) );

                if ( $loginResult->login->result == "NeedToken" ) {
                    Logger::log("Sending token {$loginResult->login->token}");
                    $details['lgtoken'] = $loginResult->login->token;
                    $loginResult = $this->c->post( WIKI_API, $details )->body;
		    $loginResult = json_decode( $loginResult );
		    Logger::log("token login result: ".print_r( $loginResult, true ));
                    if ( $loginResult->login->result != "Success" ) {
                        throw new Exception("Failed to authenticate");
                    }
                }

                Logger::log("Logged in");
	
	}

	/**
	 * Searches for the latest cables, checks if the cable has been added
	 * to the wiki already, if not returns the id in an array
	 * @return array of cable ids that need adding
	 */
	private function getCablesToAdd() {

                Logger::log("Getting latest cables");
		
		$json = $this->c->get( FEED_LATEST_CABLES )->body;
		
		$cables = json_decode( $json );
		Logger::log("Raw cables: ".print_r( $cables, true) );

		foreach ( $cables as $cable ) {
			$idsToCheck[] = $cable->identifier;
		}
		
		$idsToCheck = implode('|',$idsToCheck);

                Logger::log("Checking if cablewiki has these cables");
		$apiJson = $this->c->get(WIKI_API . "?action=query&titles=$idsToCheck&indexpageids&format=json")->body;
		
		$apiResult = json_decode( $apiJson );
		Logger::log("cablewiki result: ".print_r( $apiResult, true ));

		foreach ( $apiResult->pageids as $id ) {
			if ( $id > 0 ) {
				$idsToCreate[] = $id;
			}
		}

                Logger::log(count($idsToCreate)." cables to create");

		return $idsToCreate;
		
	}

	/**
	 * Gets a single cable from the feed
	 * @param int $id
	 * @return object cable values in an object
	 */
	private function getSingleCable( $id ) {

                Logger::log("Getting cable $id");

		$cable = $this->c->get( FEED_SINGLE_CABLE . $id .'.json' )->body;
		$cable = json_decode( $cable );
		Logger::log("raw cable: ".print_r( $cable, true ) );
		return $cable;
	
	}

	/**
	 * Takes the capitalized classified string and turns it into a normalized
	 * string
	 * @param string $classification
	 * @return string Grammar'd classification
	 */
	private function normalClassification( $classification ) {
		$search = array(  'CONFIDENTIAL',     'SECRET//NOFORN',       'SECRET',   'CONFIDENTIAL//NOFORN',     'UNCLASSIFIED',  'UNCLASSIFIED//FOR OFFICIAL USE ONLY');
		$replace = array( 'Confidential',         'Secret No Foreigners',   'Secret',     'Confidential No Foreigners',   'Unclassified',     'Unclassified For Official Use Only');
		return str_replace( $search, $replace, $classification );
	}
	
	/**
         * Builds the wiki code including the template infobox and adds
	 * categories
         * @param object $cable Cable object from self::getSingleCable
         * @return string cable converted to wikicode
         */
        private function makeWikiCode( $cable ) {
		Logger::log("Building wiki code");
                $categories = array();
		
		// Set the categories
		$categories[] = $cable->office;
		$categories[] = $this->normalClassification( $cable->classification );
		$categories[] = substr( $cable->date_sent, 0, 4 );
                $categories = array_merge($categories, $this->findCountries($cable->body));
		
		// Check if the tags are in string format
		if ( is_string( $cable->tags ) ) {
			$cable->tags = explode( ';', $cable->tags ); // move them into array format
		}
		
		// Add each tag to the categories
		if ( is_array($cable->tags) ) {
			$categories = array_merge(
				$categories,
				$this->categorizeTags($cable->tags)
			);
		}
		
		// Make some modifications
		$cable->header = str_replace( '\n', "\n", $cable->header );
		$cable->body = str_replace( array('\n',"¶"), "\n", $cable->body );
		
		// Make the wikicode
		$wikiCode = <<<EOF
{{Infobox cable
| title_orig       = {$cable->identifier}
| date             = {$cable->date_sent} 
| release_date     = {$cable->released} 
| media_type       = {$cable->classification} 
| country          = {$cable->office}
| author           = 
| subject          = {$cable->subject}  
}}

==Summary==

==Head==
<pre>{$cable->header}</pre>

==Content==
{$cable->body}


EOF;
		
		// Add the categories to the code
		$categories = array_unique($categories);
		foreach ( $categories as $cat ) {
			$wikiCode .= "[[Category:$cat]]\n";
		}
		Logger::log("raw wikicode: $wikiCode");
		return $wikiCode;
		
	}

        /**
         * Queries the wiki api to get an edit token then builds a query array
         * with the wikicode and other details to save the cable to the wiki.
         *
         * @param string $id
         * @param string $wikiCode
         */
	private function saveCable( $id, $wikiCode ) {

		$pageId = "-1";

		$apiJson = $this->c->get(WIKI_API . "?action=query&prop=info|revisions&intoken=edit&titles=$id&format=json")->body;
		$apiJson = json_decode( $apiJson );
		Logger::log("revision check: ".print_r( $apiJson, true ) );

		if ( !isset($apiJson->query->pages->$pageId->missing) ) {
		    throw new Exception("Page exists!");
		}

                // Setup the edit params
		$edit['action'] = 'edit';
		$edit['title'] = $id;
		$edit['text'] = $wikiCode;
		$edit['md5'] = md5($wikiCode);
		$edit['bot'] = "true";
		$edit['section'] = 0;
		$edit['createonly'] = "true";
		$edit['token'] = urlencode( $apiJson->query->pages->$pageId->edittoken );
		$edit['starttimestamp'] = $apiJson->query->pages->$pageId->starttimestamp;
		$edit['format'] = 'json';
		Logger::log("wiki edit params: ".print_r($edit, true));
		$c = $this->c;
		$c->headers['Content-Type'] = "application/x-www-form-urlencoded";
		
		$editResult = $c->post( WIKI_API, $edit )->body;
		$editResult = json_decode( $editResult );
		Logger::log("wiki edit result: ".print_r( $editResult, true ));
		
		if ( $editResult->edit->result != "Success" ) {
			throw new Exception( "Failed to save cable $id" );
		}

                Logger::log("Saved $id to the wiki");
	
	}

        /**
         * Oversees the scraping and saving processes and
         * gives a public interface for calling.
         */
	public function addNewCables() {
                Logger::log("Starting process");
		$this->login();

                if ( $this->id ) {
                    $idsToCreate[] = $this->id;
                } else {
                    $idsToCreate = $this->getCables();
                }
		
		foreach ( $idsToCreate as $id ) {

                        try {
                            $cable = $this->getSingleCable( $id );
                            $wikiCode = $this->makeWikiCode( $cable );
                            $this->saveCable( $id, $wikiCode );
                        } catch ( Exception $e ) {
                            Logger::log( get_class( $e ).': '.$e->getMessage() );
                        }
		
		}
		
	}

	public function addOneCable( $id ) {
	    $this->setId( $id );
	    $this->addNewCables();
	}

	/**
	 * Checks for which countries are mentioned in the body
	 * @param string $body cable body
	 * @return array array of countries found in the body
	 */
        private function findCountries( $body ) {
            $categories = array();
	    
            $countries = file_get_contents(COUNTRY_LIST);
            $countries = explode("\n", $countries);
	    $body = str_replace(array('\n',"\n","\t",'\t')," ",$body);

            foreach ( $countries as $country ) {
                $country = trim( $country );
		if ( strstr( $body, $country ) ) {
                    $categories[] = $country;
                }
            }

            return $categories;
        }

	/**
	 *
	 * @param <type> $tags
	 */
	private function categorizeTags( $tags ) {
	    $categories = array();
	    $taglist = unserialize( file_get_contents('taglist.phps') );

	    foreach ( $tags as $tag ) {
		$categories[] = $taglist[$tag];
	    }

	    return $categories;
	}


}