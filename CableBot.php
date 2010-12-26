<?php

/**
 * Copies cables from LeakFeed to a mediawiki database
 * @author Robert McLeod
 * @since 2009
 * @version 0.5
 */

class CableBot {

        private $id = false;
	private $testOnly = false;
	private $addExternalLinks = false;
	private $cablesToCreate = array();
    
	function __construct() {
		Logger::log('CableBot initialized');
		$this->c = new Curl();
	}

	/**
	 * Sets the id to get if it is a singular id that is needed
	 * @param string $id The id of the cable
	 */
        public function setId( $id ) {
            $this->id = $id;
	    $this->cablesToCreate[] = $id;
        }

	/**
	 * When true does not save cable to the wiki
	 * @param boolean $b true to not save to the wiki
	 */
	public function setTest( $b ) {
	    $this->testOnly = $b;
	}

	/**
	 * Sets whether to add an external links section
	 * @param boolean $b true to add links section
	 */
	public function setAddExternalLinks( $b ) {
	    $this->addExternalLinks = $b;
	}

	/**
	 * Logs into the wiki
	 */
	private function login() {
                Logger::log("Logging in");

                $details = array(
			'action' => 'login',
			'lgname' => WIKI_USERNAME,
			'lgpassword' => WIKI_PASSWORD,
			'format' => 'json'
		);
		
		$loginResult = $this->c->post( WIKI_API, $details )->body;
		
		$loginResult = json_decode( $loginResult );
		//Logger::log("Login result: ". print_r( $loginResult, true ) );

                if ( $loginResult->login->result == "NeedToken" ) {
                    Logger::log("Sending token {$loginResult->login->token}");
                    $details['lgtoken'] = $loginResult->login->token;
                    $loginResult = $this->c->post( WIKI_API, $details )->body;
		    $loginResult = json_decode( $loginResult );
		    //Logger::log("token login result: ".print_r( $loginResult, true ));
                    if ( $loginResult->login->result != "Success" ) {
                        throw new Exception("Failed to authenticate");
                    }
                }

                Logger::log("Logged in");
	
	}

	/**
	 * Checks if the given cable ids have been created in the wiki by
	 * returning an array of the cable ids that don't exist
	 * @param array $ids cable ids to check if are in the wiki
	 * @return array array of ids that don't exist
	 */
	private function checkIfArticleExists( $ids ) {
		$idsToCreate = array();

		$chunks = array_chunk($ids, 20);
		foreach ( $chunks as $chunk ) {

		    $idsToCheck = implode('|',$chunk);

		    Logger::log("Checking if cablewiki has these cables");
		    $apiJson = $this->c->get(WIKI_API . "?action=query&titles=$idsToCheck&indexpageids&format=json")->body;

		    $apiResult = json_decode( $apiJson );
		    //Logger::log("cablewiki result: ".print_r( $apiResult, true ));

		    for ( $id=-1; $id > -21; $id-- ) {
			    if ( !isset( $apiResult->query->pages->$id->missing ) ) continue;
			    $idsToCreate[] = $apiResult->query->pages->$id->title;
		    }
		}

		return $idsToCreate;
	}

	/**
	 * Searches for the latest cables, checks if the cable has been added
	 * to the wiki already, if not returns the id in an array
	 */
	private function getLatestCables() {

                Logger::log("Getting latest cables");
		
		$json = $this->c->get( FEED_LATEST_CABLES )->body;
		
		$cables = json_decode( $json );
		//Logger::log("Raw cables: ".print_r( $cables, true) );

		foreach ( $cables as $cable ) {
			$this->cablesToCreate[] = $cable->identifier;
		}
		
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
		//Logger::log("raw cable: ".print_r( $cable, true ) );
		return $cable;
	
	}

	/**
	 * Takes the capitalized classified string and turns it into a normalized
	 * string
	 * @param string $classification
	 * @return string Grammar'd classification
	 */
	private function normalClassification( $classification ) {
		$search = array(  'SECRET//NOFORN',        'SECRET',     'CONFIDENTIAL//NOFORN',       'CONFIDENTIAL',  'UNCLASSIFIED//FOR OFFICIAL USE ONLY',   'UNCLASSIFIED');
		$replace = array( 'Secret No Foreigners',  'Secret',     'Confidential No Foreigners', 'Confidential',  'Unclassified For Official Use Only',    'Unclassified');
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
		$cable->encoded_subject = urlencode($cable->subject);

		// Make the wikicode
		$wikiCode = <<<EOF
{{Infobox cable
| title_orig       = {$cable->identifier}
| date             = {$cable->date_sent} 
| release_date     = {$cable->released} 
| media_type       = [[{$cable->classification}]]
| country          = [[{$cable->office}]]
| author           = 
| subject          = {$cable->subject}  
}}

==Summary==

==Head==
<pre>{$cable->header}</pre>

==Content==
{$cable->body}



EOF;

		// Add external links section if wanted
		if ( $this->addExternalLinkSections ) {
		    $wikiCode .= "==External Links==
		    * [http://www.google.co.nz/search?q={$cable->identifier} Google search for identifier]
		    * [http://www.google.co.nz/search?q={$cable->encoded_subject} Google search for subject]
		    * [http://twitter.com/#search?q={$cable->identifier} Twitter search for identifier]
		    * [http://api.leakfeed.com/v1/cable/{$cable->identifier}.xml XML version of cable from LeakFeed]\n\n";
		}

		// Add the categories to the code
		$categories = array_unique($categories);
		foreach ( $categories as $cat ) {
			if ( empty( $cat ) ) continue;
			$wikiCode .= "[[Category:$cat]]\n";
		}
		//Logger::log("raw wikicode: $wikiCode");
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
		//Logger::log("revision check: ".print_r( $apiJson, true ) );

		if ( !isset($apiJson->query->pages->$pageId->missing) ) {
		    throw new Exception("$id exists in the wiki!");
		}

                // Setup the edit params
		$edit['action'] = 'edit';
		$edit['title'] = $id;
		$edit['text'] = $wikiCode;
		$edit['md5'] = md5($wikiCode);
		$edit['bot'] = "true";
		$edit['section'] = 0;
		$edit['createonly'] = "true";
		$edit['token'] = $apiJson->query->pages->$pageId->edittoken;
		$edit['starttimestamp'] = $apiJson->query->pages->$pageId->starttimestamp;
		$edit['format'] = 'json';

		//Logger::log("wiki edit params: ".print_r($edit, true));

		$c = $this->c;
		$c->headers['Content-Type'] = "application/x-www-form-urlencoded";
		
		if ( $this->testOnly ) {
		    Logger::log("Testing mode on: didn't save $id to wiki");
		    return false;
		}
		
		$editResult = $c->post( WIKI_API, $edit )->body;
		$editResult = json_decode( $editResult );
		//Logger::log("wiki edit result: ".print_r( $editResult, true ));
		
		if ( $editResult->edit->result != "Success" ) {
			throw new Exception( "Failed to save cable $id" );
		}

                Logger::log("Saved $id to the wiki");
	
	}

        /**
         * Oversees the scraping and saving processes and
         * gives a public interface for calling.
         */
	private function addCables() {
                $this->login();
		
		Logger::log(count($this->cablesToCreate)." cables given");

		$this->cablesToCreate = $this->checkIfArticleExists($this->cablesToCreate);

                Logger::log(count($this->cablesToCreate)." cables to create");

		if ( empty( $this->cablesToCreate ) ) {
		    Logger::log("No cables to process - exiting");
		    return;
		}

		foreach ( $this->cablesToCreate as $id ) {

                        try {
                            $cable = $this->getSingleCable( $id );
                            $wikiCode = $this->makeWikiCode( $cable );
                            $this->saveCable( $id, $wikiCode );
                        } catch ( Exception $e ) {
                            Logger::log( get_class( $e ).': '.$e->getMessage() );
                        }
		
		}

		Logger::log("Finished adding cables");
		
	}

	/**
	 * Finds the latest cables and adds them to the wiki
	 */
	public function addLatestCables() {
	    $this->getLatestCables();
	    $this->addCables();
	}

	/**
	 * Adds a single cable to the wiki
	 * @param string $id the cable reference id
	 */
	public function addSingleCable( $id ) {
	    $this->setId( $id );
	    $this->addCables();
	}

	/**
	 * Add cables from the array
	 * @param array $array Array of cable ids
	 */
	public function addCablesFromArray( $array ) {
	    $this->cablesToCreate = $array;
	    $this->addCables();
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

	    // strip out newlines and tabs
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
	 * Uses the list of tags to assign categories based on cable tags into
	 * the wiki
	 * @param array $tags array of tags in the cable
	 * @return array of categories
	 */
	private function categorizeTags( $tags ) {
	    $categories = array();
	    $taglist = unserialize( file_get_contents( TAGLIST ) );

	    foreach ( $tags as $tag ) {
		$categories[] = trim($taglist[$tag]);
	    }

	    return $categories;
	}


}