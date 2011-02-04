<?php

/**
 * Saves the cables to the cable wiki using templates
 * @author Robert McLeod
 * @since Febuary 2011
 * @copyright 2011 Robert McLeod
 * @version 0.2.1
 */
class SaveCable {

	private $wikimate = null;
        private $overwrite = OVERWITE;
	
	function __construct() {
	
		$this->wikimate = new Wikimate;
		
	}

        public function setOverwrite( $b ) {
            $this->overwrite = $b;
        }

	private function makeAnalysisPage( $cable ) {
	
		$page = $this->wikimate->getPage( $cable['identifier'] );
		
		if ( $page->exists() ) {
                    Logger::log("Analysis for {$cable['identifier']} exists");
                    if ( $this->overwrite ) {
                        Logger::log("Overwriting {$cable['identifier']} Analysis");
                    } else {
                        throw new Exception("Cannot overwrite Analysis for {$cable['identifier']}");
                    }
                }

                $text = ANALYSIS_TEMPLATE;

		$search = array_keys( $cable->toArray() );
		foreach( $search as &$s ) { $s = '{'.$s.'}'; }
		$replace = $cable->toArray();

		$text = str_replace( $search, $replace, $text );

                // Don't forget the categories
                foreach ( $cable->categories as $category ) {
                    $text.= "[[Category:$category]]\n";
                }

		$page->setText( $text );
	
	}
	
	private function makeCablePage( $cable ) {
	
		$page = $this->wikimate->getPage( "Cable:{$cable['identifier']}" );
		
		if ( $page->exists() ) {
                    Logger::log("Cable for {$cable['identifier']} exists");
                    if ( $this->overwrite ) {
                        Logger::log("Overwriting {$cable['identifier']} cable");
                    } else {
                        throw new Exception("Cannot overwrite Cable for {$cable['identifier']}");
                    }
                }
		
		$text = CABLE_TEMPLATE;
		
		$search = array_keys( $cable->toArray() );
		foreach( $search as &$s ) { $s = '{'.$s.'}'; }
		$replace = $cable->toArray();
		
		$text = str_replace( $search, $replace, $text );
		
		$page->setText( $text );
	
	}

        /**
         * Calls the private methods to create the analysis and cable pages
         * @param Cable $cable the cable object
         */
	public function create( $cable ) {
		
		$this->makeAnalysisPage( $cable );
		$this->makeCablePage( $cable );
	
	}

}