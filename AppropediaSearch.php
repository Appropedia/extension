<?php

/**
 * This class contains all PHP code related to search customization
 * (there's some relevant JavaScript code at Appropedia.js too)
 */
class AppropediaSearch {

	/**
	 * Customize the content of search results
	 */
	public static function onShowSearchHit( $searchPage, $result, $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html ) {

		// Remove time from date, per mostly useless and noisy (only works in English and some other languages)
		$date = substr( $date, strpos( $date, ', ' ) + 1 );
	}

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
					NS_PRELOAD_TALK,
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
					NS_PRELOAD,
					NS_CATEGORY,
					274, // Widgets
					828, // Lua modules
				],
				'namespace-messages' => [ 'categories, templates, widgets, Lua modules, help pages, preloads, Appropedia and MediaWiki namespaces' ]
			],
		];
	}

	/**
	 * Cusomize the search profile form
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
			$form .= '<option value="">' . wfMessage( "appropedia-search-page-type-any" )->text() . '</option>';
			$options = [
				'Projects',
				'Devices',
				'Organizations',
				'Skills',
				'Papers',
				'Books',
				'Literature reviews'
			];
			foreach ( $options as $text ) {
				$value = 'incategory:' . str_replace( ' ', '_', $text );
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// Sustainable Development Goal
			$form .= '<select id="search-filter-page-sdg" style="width: 58px;">';
			$form .= '<option value="">' . wfMessage( "appropedia-search-page-sdg-any" )->text() . '</option>';
			$options = [
				'SDG01 No poverty',
				'SDG02 Zero hunger',
				'SDG03 Good health and well-being',
				'SDG04 Quality education',
				'SDG05 Gender equality',
				'SDG06 Clean water and sanitation',
				'SDG07 Affordable and clean energy',
				'SDG08 Decent work and economic growth',
				'SDG09 Industry innovation and infrastructure',
				'SDG10 Reduced inequalities',
				'SDG11 Sustainable cities and communities',
				'SDG12 Responsible consumption and production',
				'SDG13 Climate action',
				'SDG14 Life below water',
				'SDG15 Life on land',
				'SDG16 Peace justice and strong institutions',
				'SDG17 Partnerships for the goals',
			];
			foreach ( $options as $text ) {
				$value = 'incategory:' . str_replace( ' ', '_', $text );
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// Language
			$form .= '<select id="search-filter-page-language" style="width: 87px;">';
			$form .= '<option value="">' . wfMessage( "appropedia-search-page-language-any" )->text() . '</option>';
			$options = [
				'English' => 'en',
				'French' => 'fr',
				'German' => 'de',
				'Italian' => 'it',
				'Spanish' => 'es',
				'Portuguese' => 'pt',
			];
			foreach ( $options as $text => $value ) {
				$value = 'inlanguage:'. $value;
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';
		}

		// File pages
		if ( $profile === 'files' ) {

			// File type
			$filetype = strtolower( $filetype );
			$form .= '<select id="search-filter-file-type" style="width: 55px;">';
			$form .= '<option value="">' . wfMessage( "appropedia-search-file-type-any" )->text() . '</option>';
			$options = [
				'Images' => 'bitmap',
				'Drawings' => 'drawing',
				'Documents' => 'office',
				'Code' => 'text',
				'Videos' => 'video',
				'Audios' => 'audio',
			];
			foreach ( $options as $text => $value ) {
				$value = 'filetype:' . $value;
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';

			// File MIME
			$filemime = $request->getText( 'filemime' );
			$form .= '<select id="search-filter-file-mime" style="width: 69px;">';
			$form .= '<option value="">' . wfMessage( "appropedia-search-file-format-any" )->text() . '</option>';
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
			$form .= '<option value="">' . wfMessage( "appropedia-search-file-license-any" )->text() . '</option>';
			$options = [
				'CC-BY-SA-4.0' => 'CC-BY-SA-4.0 files',
				'CC-BY-SA-3.0' => 'CC-BY-SA-3.0 files',
				'CC-BY-SA-2.0' => 'CC-BY-SA-2.0 files',
				'CC0-1.0' => 'CC0-1.0 files',
				'GFDL' => 'GFDL files',
				'GPL' => 'GPL files',
				'LGPL' => 'LGPL files',
				'Public domain' => 'Public domain files',
			];
			foreach ( $options as $text => $value ) {
				$value = 'incategory:' . $value;
				$form .= '<option' . ( in_array( $value, $terms ) ? ' selected' : '' ) . ' value="' . $value . '">' . $text . '</option>';
			}
			$form .= '</select>';
		}

		if ( $profile === 'users' ) {

			// User location
			$location = $request->getText( 'location' );
			$form .= '<select id="search-filter-user-location" style="width: 77px;">';
			$form .= '<option value="">' . wfMessage( "appropedia-search-user-location-any" )->text() . '</option>';
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