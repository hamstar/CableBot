<?php

/**
 * Represents a cable
 * Can be referenced like an object or an array
 *
 * @author Robert McLeod
 * @since Febuary 2011
 * @copyright 2011 Robert McLeod
 * @version 0.5
 */
class Cable implements ArrayAccess {

    const PARTIAL_TEXT = 'This record is a partial extract of the original cable. The full text of the original cable is not available.';

    private $originalJson = null;
    private $attributes = array();

    function __construct( $json ) {

        if ( !$json = json_decode( $json ) ) {
            $spider = new CableSpider;
            $json = $spider->getJson( $input );
            unset($spider);
        }

	$this->originalJson = $json;
	$cable = json_decode( $json );

	if ( !is_array( $cable->tags ) ) {
            $cable->tags = explode( ';', $cable->tags );
        } else if ( strstr( $cable->tags[0], ';' ) ) {
            $cable->tags = explode( ';', $cable->tags[0] );
        }

	$this->attributes = array(
	    'identifier' => $cable->identifier,
	    'id' => $cable->identifier,
	    'released' => $cable->released,
	    'date_sent' => $cable->date_sent,
	    'date' => $cable->date_sent,
	    'created' => $cable->date_sent,
	    'origin' => $cable->office,
	    'from' => $cable->office,
	    'office' => $cable->office,
	    'tags' =>  $cable->tags,
     'subject' => $cable->subject,
	    /** Stuff that needs parsing **/
	    'header' => self::parseHeader($cable->header),
	    'head' => self::parseHeader($cable->header),
	    'body' => self::parseBodyContent( $cable->body ),
	    'content' => self::parseBodyContent( $cable->body ),
	    'bodyheader' => self::parseBodyHeader( $cable->body ),
	    'classification' => self::parseClassification( $cable->classification ),
	    'taglist' => self::parseTags( $cable->tags ),
	    'author' => self::parseAuthor( $cable->body ),
	    'urgency' => self::parseUrgency( $cable->header ),
	    'destination' => self::parseDestination( $cable->header ),
	    'info' => self::parseInfo( $cable->header ),
	);

	$this->attributes['categories'] = self::parseCategories( $this );

    }

    /**
     *
     *
     ************** MAGIC METHODS
     *
     *
     */

    public function __get( $attr ) {
	return $this->attributes[$attr];
    }

    public function __invoke() {
	return $this->toArray();
    }

    public function __toString($php=false) {
	if ( $php ) {
	    return $this->toPHP();
	}

	return $this->toJSON();
    }

    /**
     *
     * 
     ************** ARRAY METHODS
     * 
     * 
     */

    public function set($key, $value) {
	$this->attributes[$key] = $value;
    }

    public function offsetExists($offset) {
	return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset) {
	return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value) {
	$this->set($offset, $value);
    }

    public function offsetUnset($offset) {
	unset($this->attributes[$offset]);
    }

    /**
     *
     *
     *************** ACCESSOR METHODS
     *
     *
     */

    /**
     * Drops an array
     * @return array of attributes 
     */
    public function toArray() {
	return $this->attributes;
    }

    /**
     * Drops a JSON string
     * @param boolean $original true to return the original json response
     * @return string json encoded cable attributes
     */
    public function toJson( $original=false ) {
	if ( $original ) {
	    return $this->originalJson;
	}
    }

    /**
     * Drops a serialized cable array
     * @return string Serialized array of cable
     */
    public function toPHP() {
	return serialize( $this->attributes );
    }

    /**
     *
     *
     ****************  PARSING METHODS
     *
     *
     */

    /**
     * Returns a nicer formatted string if it is a
     * partial cable otherwise returns the header.
     *
     * @param string $header The header of the cable
     * @return string The header of the cable
     */
    public static function parseHeader( $header ) {
        if ( $header == self::PARTIAL_TEXT ) {
            return str_replace( 'cable. ', "cable.\n", $header );
        }
        
        return $header;
    }

    /**
     * Takes the capitalized classified string and turns it into a normalized
     * string
     * @param string $classification
     * @return string Grammar'd classification
     */
    public static function parseClassification( $classification ) {
	    
	$classifications = array(
	    'SECRET//NOFORN' => 'Secret No Foreigners',
	    'SECRET' => 'Secret',
	    'CONFIDENTIAL//NOFORN' => 'Confidential No Foreigners',
	    'CONFIDENTIAL' => 'Confidential',
	    'UNCLASSIFIED//FOR OFFICIAL USE ONLY' =>  'Unclassified For Official Use Only',
	    'UNCLASSIFIED' => 'Unclassified'
	);

	return $classifications[ $classification ];

    }

    /**
     * Searches the body and tags for suitable categories.  Uses two external
     * files which provide country names and tag to description list.
     * @param Cable $cable the cable to check categories
     * @return array array of categories this cable would fall into
     */
    public static function parseCategories( $cable ) {
	$categories = array();
	
	// Add the full categories for this tag
	$taglist = unserialize( file_get_contents( TAGLIST ) );
	foreach ( $cable->tags as $tag ) {
            if ( !isset($taglist[$tag]) ) continue;
	    $categories[] = trim($taglist[$tag]);
	}

	// Find what countries this cable talks about
	$countries = unserialize( file_get_contents( COUNTRY_LIST ) );
	// strip out newlines and tabs
	$body = str_replace(array('\n',"\n","\t",'\t')," ",$cable->body);
	foreach ( $countries as $country ) {
	    $country = trim( $country );
	    if ( strstr( $body, $country ) ) {
		$categories[] = $country;
	    }
	}

	return array_unique( $categories );
    }

    /**
     * Find the urgency from the cable header
     * @param string $header header text
     * @return string urgency description
     */
    public static function parseUrgency( $header ) {

	if ( $header == self::PARTIAL_TEXT ) {
	    return '';
	}

	$lines = explode("\n", $header );
	$code = substr( $lines[1], 0, 2);

	unset( $lines );

	switch ( $code ) {
	case 'OO':
	case 'O ':
		return 'Very Urgent';
		break;
	case 'P ':
	case 'PP':
		return 'Urgent';
		break;
	case 'ZZ':
	case 'Z ':
		return 'Priority';
		break;
	case 'RR':
	case 'R ':
		return 'Routinet';
		break;
	default:
		return '';
		break;
	}

    }

    /**
     * Finds the author of the cable in a wiki link
     * @param string $body cable body
     * @return string The author of the cable
     */
    public static function parseAuthor( $body ) {

	    $content = trim( $body );
	    $line = substr( $body, strrpos( $body, "\n") );
	    $line = trim( $line );

        $words = preg_split( '@\W@', $line );
        $author = $words[count($words)-1];

	    return ucfirst( $author );

    }

    /**
     * Return a tag list with the tags inside wiki links
     * @param array $tags array of tags on the cable
     * @return string comma separated string of cable tags in wiki links
     */
    public static function parseTags( $tags ) {

	    return implode(', ', $tags);

    }

    /**
     * Gets the cable destination from the header text
     * @param string $header header text
     * @return string destination the cable was sent to
     */
    public static function parseDestination( $header ) {

	if ( $header == self::PARTIAL_TEXT ) {
	    return '';
	}

	if ( preg_match('@\nTO (.*)\n@', $header, $m ) ) {
	    $destination = substr( $m[1], 0, strpos( $m[1], '/' ) );
	    $taglist = unserialize( file_get_contents( TAGLIST ) );
            $destination = ( isset( $taglist[$destination] ) ) ? $taglist[$destination] : $destination;
            return $destination;
	}

	return '';

    }

    /**
     *
     * @param <type> $header
     * @return string
     */
    public static function parseInfo( $header ) {

	if ( $header == self::PARTIAL_TEXT ) {
	    return '';
	}

	if ( stristr( $header, 'info' ) ) {
	    $taglist = unserialize( file_get_contents( TAGLIST ) );
	} else {
            return '';
        }

	$infolines = substr( $header, strpos( $header, "\nINFO ")+6 );
	$infolines = explode( "\n", $infolines );

	foreach ( $infolines as $line ) {
	    $cc = substr( $line, 0, strpos( $line, '/' ) );
            $cc = ( isset( $taglist[$cc] ) ) ? $taglist[$cc] : $cc;
            $info[] = $cc;
	}

	return implode( ',', $info );

    }

    public static function parseReferences( $body ) {



    }

    public static function parseBodyHeader( $body ) {

        list( $bodyHeader ) = explode( "¶", $body );

        return $bodyHeader;

    }

    public static function parseBodyContent( $body ) {

        $paras = explode( "¶", $body );
        
        unset( $paras[0] );
        
        foreach ( $paras as &$para ) {
            $para = trim( $para );
        }
        
        return implode("\n<hr/>\n", $paras );

    }

}