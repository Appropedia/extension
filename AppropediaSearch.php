<?php

use MediaWiki\MediaWikiServices;

/**
 * This class contains all PHP code related to search customization
 *
 * @note There's relevant code at Appropedia.js too
 */
class AppropediaSearch {

	/**
	 * Customize the title of search results
	 */
	public static function onShowSearchHitTitle( Title &$title, &$titleSnippet, SearchResult $result, $terms, SpecialSearch $specialSearch, array &$query, array &$attributes ) {

		// Show the display title rather than the title
		// See https://phabricator.wikimedia.org/T65975
		$dbr = wfGetDB( DB_REPLICA );
		$displayTitle = $dbr->selectField( 'page_props', 'pp_value', [ 'pp_propname' => 'displaytitle', 'pp_page' => $title->getArticleId() ] );
		if ( $displayTitle ) {
			$titleSnippet = $displayTitle;
		}
	}

	/**
	 * Cusomize the default search profile
	 */
	public static function onSpecialPageBeforeExecute( $special ) {
		if ( $special->getName() === 'Search' ) {
			$request = $special->getRequest();
			$profile = $request->getText( 'profile' );
			if ( !$profile ) {
				$request->setVal( 'profile', 'pages' );
			}
		}
	}

	/**
	 * Customize the search profiles
	 */
	public static function onSpecialSearchProfiles( &$profiles ) {
		$profiles = [
			'pages' => [
				'message' => 'appropedia-searchprofile-pages',
				'tooltip' => 'searchprofile-articles-tooltip',
				'namespaces' => [ NS_MAIN ],
				'namespace-messages' => [ 'content pages' ]
			],
			'files' => [
				'message' => 'appropedia-searchprofile-files',
				'tooltip' => 'searchprofile-articles-tooltip',
				'namespaces' => [ NS_FILE ],
				'namespace-messages' => [ 'files' ]
			],
			'users' => [
				'message' => 'appropedia-searchprofile-users',
				'tooltip' => 'searchprofile-articles-tooltip',
				'namespaces' => [ NS_USER ],
				'namespace-messages' => [ 'user pages' ]
			],
			'talks' => [
				'message' => 'appropedia-searchprofile-talks',
				'tooltip' => 'searchprofile-articles-tooltip',
				'namespaces' => [
					NS_TALK,
					NS_USER_TALK,
					NS_PROJECT_TALK,
					NS_MEDIAWIKI_TALK,
					NS_TEMPLATE_TALK,
					NS_HELP_TALK,
					//NS_PRELOAD_TALK,
					NS_CATEGORY_TALK,
					275, // Widget talk
					829, // Lua modules talk
				],
				'namespace-messages' => [ 'talk pages' ]
			],
			'other' => [
				'message' => 'appropedia-searchprofile-other',
				'tooltip' => 'searchprofile-articles-tooltip',
				'namespaces' => [
					NS_PROJECT,
					NS_MEDIAWIKI,
					NS_TEMPLATE,
					NS_HELP,
					//NS_PRELOAD,
					NS_CATEGORY,
					274, // Widgets
					828, // Lua modules
				],
				'namespace-messages' => [ 'categories, templates, widgets, Lua modules, help pages, preloads, Appropedia and MediaWiki namespaces' ]
			],
		];
	}

	/**
	 * Customize the search profile form
	 *
	 * This new markup interacts with Appropedia.js
	 */
	public static function onSpecialSearchProfileForm( $special, &$form, &$profile, $term, array $opts ) {
		$form = '<div class="mw-search-profile-form">';
		$request = $special->getRequest();
		$search = $request->getText( 'search' );
		$terms = explode( ' ', $search );

		// Content pages
		if ( $profile === 'pages' ) {

			// Page type
			$form .= '<select id="search-filter-page-type" style="width: 55px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-page-type-any' )->text() . '</option>';
			$options = [
				$special->msg( 'appropedia-search-page-type-projects' )->text() => 'Projects',
				$special->msg( 'appropedia-search-page-type-guides' )->text() => 'How tos',
				$special->msg( 'appropedia-search-page-type-topics' )->text() => 'Topics',
				$special->msg( 'appropedia-search-page-type-maps' )->text() => 'Maps',
				$special->msg( 'appropedia-search-page-type-organizations' )->text() => 'Organizations',
				$special->msg( 'appropedia-search-page-type-devices' )->text() => 'Devices',
				$special->msg( 'appropedia-search-page-type-essays' )->text() => 'Essays',
				$special->msg( 'appropedia-search-page-type-papers' )->text() => 'Papers',
				$special->msg( 'appropedia-search-page-type-books' )->text() => 'Books',
				$special->msg( 'appropedia-search-page-type-literature-reviews' )->text() => 'Literature reviews'
			];
			foreach ( $options as $text => $value ) {
				$value = 'incategory:' . str_replace( ' ', '_', $value );
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// Sustainable Development Goal
			$form .= '<select id="search-filter-page-sdg" style="width: 58px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-page-sdg-any' )->text() . '</option>';
			$options = [
				$special->msg( 'appropedia-search-page-sdg-01' )->text() => 'SDG01 No poverty',
				$special->msg( 'appropedia-search-page-sdg-02' )->text() => 'SDG02 Zero hunger',
				$special->msg( 'appropedia-search-page-sdg-03' )->text() => 'SDG03 Good health and well-being',
				$special->msg( 'appropedia-search-page-sdg-04' )->text() => 'SDG04 Quality education',
				$special->msg( 'appropedia-search-page-sdg-05' )->text() => 'SDG05 Gender equality',
				$special->msg( 'appropedia-search-page-sdg-06' )->text() => 'SDG06 Clean water and sanitation',
				$special->msg( 'appropedia-search-page-sdg-07' )->text() => 'SDG07 Affordable and clean energy',
				$special->msg( 'appropedia-search-page-sdg-08' )->text() => 'SDG08 Decent work and economic growth',
				$special->msg( 'appropedia-search-page-sdg-09' )->text() => 'SDG09 Industry innovation and infrastructure',
				$special->msg( 'appropedia-search-page-sdg-10' )->text() => 'SDG10 Reduced inequalities',
				$special->msg( 'appropedia-search-page-sdg-11' )->text() => 'SDG11 Sustainable cities and communities',
				$special->msg( 'appropedia-search-page-sdg-12' )->text() => 'SDG12 Responsible consumption and production',
				$special->msg( 'appropedia-search-page-sdg-13' )->text() => 'SDG13 Climate action',
				$special->msg( 'appropedia-search-page-sdg-14' )->text() => 'SDG14 Life below water',
				$special->msg( 'appropedia-search-page-sdg-15' )->text() => 'SDG15 Life on land',
				$special->msg( 'appropedia-search-page-sdg-16' )->text() => 'SDG16 Peace justice and strong institutions',
				$special->msg( 'appropedia-search-page-sdg-17' )->text() => 'SDG17 Partnerships for the goals',
			];
			foreach ( $options as $text => $value ) {
				$value = 'incategory:' . str_replace( ' ', '_', $value );
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// Language
			$services = MediaWikiServices::getInstance();
			$lb = $services->getDBLoadBalancer();
			$dbr = $lb->getConnection( DB_REPLICA );
			$query = $dbr->newSelectQueryBuilder()
				->select( 'DISTINCT pp_value AS language' )
				->from( 'page_props' )
				->where( [ 'pp_propname' => 'pagelanguage' ] );
			$results = $query->fetchResultSet();
			$userLanguage = $special->getContext()->getLanguage()->getCode();
			$languageNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();
			$options = [ 'English' => 'en' ];
			foreach ( $results as $result ) {
				$value = $result->language;
				$text = ucfirst( $languageNameUtils->getLanguageName( $value, $userLanguage ) );
				$options[ $text ] = $value;
			}
			ksort( $options );
			$form .= '<select id="search-filter-page-language" style="width: 87px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-page-language-any' )->text() . '</option>';
			foreach ( $options as $text => $value ) {
				$value = 'inlanguage:'. $value;
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// Year
			$form .= '<select id="search-filter-page-year" style="width: 56px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-page-year-any' )->text() . '</option>';
			$options = [ 2024, 2023, 2022, 2021, 2020, 2019, 2018, 2017, 2016, 2015, 2014, 2013, 2012, 2011, 2010, 2009, 2008, 2007, 2006 ];
			foreach ( $options as $year ) {
				$value = 'incategory:' . $year;
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $year . '</option>';
			}
			$form .= '</select>';
		}

		// File pages
		if ( $profile === 'files' ) {

			// File type
			$form .= '<select id="search-filter-file-type" style="width: 55px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-file-type-any' )->text() . '</option>';
			$options = [
				$special->msg( 'appropedia-search-file-type-images' )->text() => 'bitmap',
				$special->msg( 'appropedia-search-file-type-drawings' )->text() => 'drawing',
				$special->msg( 'appropedia-search-file-type-documents' )->text() => 'office',
				$special->msg( 'appropedia-search-file-type-code' )->text() => 'text',
				$special->msg( 'appropedia-search-file-type-videos' )->text() => 'video',
				$special->msg( 'appropedia-search-file-type-audios' )->text() => 'audio',
			];
			foreach ( $options as $text => $value ) {
				$value = 'filetype:' . $value;
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// File MIME
			$filemime = $request->getText( 'filemime' );
			$form .= '<select id="search-filter-file-mime" style="width: 69px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-file-format-any' )->text() . '</option>';
			$options = [
				'JPG' => 'image/jpeg',
				'PNG' => 'image/png',
				'GIF' => 'image/gif',
				'SVG' => 'image/svg+xml',
				'PDF' => 'application/pdf',
			];
			foreach ( $options as $text => $value ) {
				$value = 'filemime:' . $value;
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// File license
			$form .= '<select id="search-filter-file-license" style="width: 72px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-file-license-any' )->text() . '</option>';
			$options = [
				'CC-BY-SA-4.0' => 'CC-BY-SA-4.0 files',
				'CC-BY-SA-3.0' => 'CC-BY-SA-3.0 files',
				'CC-BY-SA-2.0' => 'CC-BY-SA-2.0 files',
				'CC0-1.0' => 'CC0-1.0 files',
				'GFDL' => 'GFDL files',
				'GPL' => 'GPL files',
				'LGPL' => 'LGPL files',
				$special->msg( 'appropedia-search-file-license-public-domain' )->text() => 'Public domain files',
			];
			foreach ( $options as $text => $value ) {
				$value = 'incategory:' . str_replace( ' ', '_', $value );
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';
		}

		if ( $profile === 'users' ) {

			// User location
			$location = $request->getText( 'location' );
			$form .= '<select id="search-filter-user-location" style="width: 77px;">';
			$form .= '<option value="">' . $special->msg( 'appropedia-search-user-location-any' )->text() . '</option>';
			$options = [ 'Argentina', 'Australia', 'Bangladesh', 'Belgium', 'Bolivia', 'Cambodia', 'Canada', 'China', 'Colombia', 'Costa Rica',
				'Denmark', 'Ecuador', 'El Salvador', 'Ethiopia', 'England', 'France', 'Germany', 'Guatemala', 'Haiti', 'India', 'Indonesia', 'Italy',
				'Japan', 'Jordan', 'Kenya', 'Korea', 'Mexico', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua', 'Nigeria', 'Panama', 'Philippines',
				'Portugal', 'Scotland', 'Senegal', 'South Africa', 'Sweden', 'Switzerland', 'Taiwan', 'Tanzania', 'Thailand', 'Turkey',
				'United Kingdom', 'United States', 'Wales'
			];
			foreach ( $options as $text ) {
				$value = 'incategory:' . str_replace( ' ', '_', $text );
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';
		}

		$form .= '</div>';
	}
}