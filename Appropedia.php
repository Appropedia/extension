<?php

use MediaWiki\MediaWikiServices;

class Appropedia {

	/**
	 * Customize search results
	 */
	public static function onShowSearchHit( $searchPage, $result, $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html ) {

	    // Remove time from date, per mostly useless and noisy (only works in English and some other languages)
	    $date = substr( $date, strpos( $date, ', ' ) + 1 );
	}
}