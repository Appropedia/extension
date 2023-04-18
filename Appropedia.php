<?php

use RequestContext;

class Appropedia {

	/**
	 * Add JS and CSS specific to Appropedia
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModules( 'ext.Appropedia' );
		$out->addModuleStyles( 'ext.Appropedia.styles' );
	}

	/**
	 * Customize interface messages
	 */
	public static function onMessagesPreLoad( $title, &$message, $code ) {
		if ( $code === 'qqx' ) {
			return;
		}

		$parts = explode( '/', $title );
		$key = $parts[0];
		$key = strtolower( $key );
		switch ( $key ) {

			// Unwanted elements are generally hidden via CSS
			// but the following messages require special treatment
			// due to various technical reasons
			case 'privacy': // See onSkinAddFooterLinks
			case 'disclaimers': // See onSkinAddFooterLinks
			case 'histlegend': // @todo Hide via CSS
			case 'newarticletext':
			case 'hcaptcha-createaccount':
			case 'hcaptcha-edit':
				$message = '';
				break;

			// Override messages
			case 'copyrightwarning':
				$message = wfMessage( "appropedia-page-edit-warning" )->text();
				break;

			case 'anoneditwarning':
				$message = wfMessage( "appropedia-anon-edit-warning" )->text();
				break;

			case 'editnotice-2':
				$context = RequestContext::getMain();
				$title = $context->getTitle();
				$user = $context->getUser()->getUserPage();
				if ( $title->equals( $user ) ) {
					break;
				}
				$link = $title->getTalkPage()->getFullURL( [ 'action' => 'edit', 'section' => 'new' ] );
				$message = wfMessage( 'appropedia-user-edit-warning', $link )->text();
				break;

			case 'editnotice-8':
				$page = 'Appropedia:UI'; // @todo Should probably be elsewhere
				$message = wfMessage( 'appropedia-interface-edit-warning', $talk )->text();
				break;

			case 'editnotice-10':
				$page = 'Appropedia:Templates'; // @todo Should probably be elsewhere
				break;

			case 'categorytree-member-num':
				$message = "($4)";
				break;

			case 'noarticletext':
				$context = RequestContext::getMain();
				$title = $context->getTitle();
				$namespace = $title->getNamespace();
				$action = in_array( $namespace, [ 0, 2, 4, 12 ] ) ? 'veaction' : 'action';
				$preload = $namespace === 2 ? 'Preload:User' : null;
				$link = $title->getFullURL( [ $action => 'edit', 'preload' => $preload ] );
				$text = wfMessage( 'appropedia-create-page' )->text();
				$message = '[' . $link . '<span class="mw-ui-button mw-ui-progressive">' . $text . '</span>]';
				break;
		}
	}

	/**
	 * Customize logo and sidebar
	 *
	 * This hook changes the logo and sidebar depending on the category
	 * so we can offer extra branding to some projects
	 * like https://www.appropedia.org/SELF
	 */
	public static function onBeforeInitialize( Title &$title ) {
		global $wgSitename, $wgLogos, $wgHooks;
		$categories = $title->getParentCategories();
		$categories = array_keys( $categories );
		if ( in_array( 'Category:SELF', $categories ) ) {
			$wgSitename = 'Surgical Education Learners Forum';
			$wgLogos['icon'] = '/logos/SELF-icon.png';
			$wgLogos['tagline'] = [
				'src' => '/logos/Appropedia-powered.png',
				'width' => 135,
				'height' => 15
			];
			unset( $wgLogos['wordmark'] );
			$wgHooks['SkinBuildSidebar'][] = function ( Skin $skin, &$sidebar ) {
				$sidebar = [];
				$skin->addToSidebar( $sidebar, 'Sidebar-SELF' );
			};
		}
	}

	/**
	 * Add Hotjar tracking for non-admins
	 */
	public static function onSkinAfterBottomScripts( $skin, &$text ) {
		$user = $skin->getUser();
		$groups = $user->getGroups();
		if ( in_array( 'sysop', $groups ) ) {
			return; // Don't track admins
		}
		$text .= "<script>
		(function(h,o,t,j,a,r){
			h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
			h._hjSettings={hjid:1531886,hjsv:6};
			a=o.getElementsByTagName('head')[0];
			r=o.createElement('script');r.async=1;
			r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
			a.appendChild(r);
		})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
		</script>";
	}

	/**
	 * Customize footer links
	 */
	public static function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerlinks ) {
		if ( $key === 'places' ) {
			$footerlinks['policies'] = $skin->footerLink( 'appropedia-policies', 'policiespage' );
			$footerlinks['contact'] = $skin->footerLink( 'appropedia-contact', 'contactpage' );
		};
		return false; // Prevent other extensions (like MobileFrontend) from adding more links
	}

	/**
	 * Make "external" links like [https://www.appropedia.org/Water Water] behave as internal links
	 */
	public static function onLinkerMakeExternalLink( &$url, &$text, &$link, &$attribs, $linktype ) {
		global $wgServerName;
		$result = parse_url( $url );
		if ( $result and array_key_exists( 'host', $result ) and $result['host'] === $wgServerName ) {
			$attribs['target'] = '_self';
			$attribs['class'] = str_replace( 'external', '', $attribs['class'] );
		}
	}

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
	 * Cusomize default search profile
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
	 * Customize search profiles
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
	 * Cusomize search profile form
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
			$form .= '<option value="">Any type</option>';
			$options = [
				'Projects',
				'Devices',
				'Organizations',
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
			$form .= '<option value="">Any SDG</option>';
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
			$form .= '<option value="">Any language</option>';
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
			$form .= '<option value="">Any type</option>';
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
			$form .= '<option value="">Any format</option>';
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
			$form .= '<option value="">Any license</option>';
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
			$form .= '<option value="">Any location</option>';
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


	/**
	 * Fix pages
	 */
	function onParserPreSaveTransformComplete( Parser $parser, string &$text ) {
		$page = $parser->getPage();
		$namespace = $page->getNamespace();
		switch ( $namespace ) {
			case 0:
				$text = Appropedia::fixContentPage( $text );
				break;
			case 2:
				$text = Appropedia::fixUserPage( $text );
				break;
			case 6:
				$text = Appropedia::fixFilePage( $text );
				break;
			case 14:
				$text = Appropedia::fixCategoryPage( $text );
				break;
		}
	}

	function fixContentPage( $text ) {
		return $text;
	}

	function fixUserPage( $text ) {
		return $text;
	}

	function fixCategoryPage( $text ) {
		return $text;
	}

	/**
	 * Fix file page
	 *
	 * This ugly contraption is here because Extension:UploadWizard has hard-coded
	 * the structure of the file pages it creates, so we can't modify them via config
	 * Therefore, we check every single page save and if it has the structure of
	 * a file page created by Upload Wizard, we transform it to our preferred structure
	 */
	function fixFilePage( $text ) {
		if ( preg_match( '/=={{int:filedesc}}==
{{Information
\|description={{en\|1=(.*)}}
\|date=(.*)
\|source=(.*)
\|author=(.*)
\|permission=(.*)
\|other versions=(.*)
}}

=={{int:license-header}}==
{{(.*)}}
*(.*)/s', $text, $matches ) ) {

			// Get data
			$description = trim( $matches[1] );
			$date = $matches[2];
			$source = $matches[3];
			$author = $matches[4];
			$permission = $matches[5];
			$otherVersions = $matches[6];
			$license = $matches[7];
			$licenseDetails = $matches[8];

			// Process data
			if ( $source === '{{own}}' ) {
				$source = 'Own work';
			}
			if ( preg_match( '/\[\[([^|]+)\|[^]]+\]\]/', $author, $matches ) ) {
				$author = $matches[1];
			}
			if ( $license === 'subst:uwl' ) {
				$license = null; // Unknown license
			} else if ( preg_match( '/self\|(.*)/', $license, $matches ) ) {
				$license = strtoupper( $matches[1] );
			} else {
				$license = strtoupper( $license );
			}
			if ( $license === 'PD' ) {
			  $license = 'Public domain';
			}
			if ( $license === 'FAIR USE' ) {
			  $license = 'Fair use';
			}
			if ( $licenseDetails ) {
				$license = $licenseDetails;
			}

			$params = [
				'date' => $date,
				'author' => $author,
				'source' => $source,
				'license' => $license,
			];
			$params = array_filter( $params );

			// Build wikitext
			$text = "$description\n\n{{File data";
			foreach ( $params as $param => $value ) {
				$text .= "\n| $param = $value";
			}
			$text .= "\n}}";
		}

		// Also customize file pages created via Special:Upload
		if ( preg_match( '/== Summary ==
(.*)
== Licensing ==
{{(.*)}}/s', $text, $matches ) ) {

			// Get data
			$description = trim( $matches[1] );
			$license = $matches[2];
		
			// Build wikitext
			$text = "$description

{{File data
| license = $license
}}";
		}
		return $text;
	}
}