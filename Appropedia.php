<?php

class Appropedia {

	/**
	 * Add JS and CSS specific to Appropedia
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModules( 'ext.Appropedia' );
		$out->addModuleStyles( 'ext.Appropedia.styles' );
		self::addGoogleTagManager( $out, $skin );
	}

	/**
	 * Customize the logo and sidebar
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
	 * Add Google Tag Manager
	 */
	public static function addGoogleTagManager( $out, $skin ) {
		$user = $skin->getUser();
		$groups = $user->getGroups();
		if ( in_array( 'sysop', $groups ) ) {
			return; // Don't track admins
		}
		$out->addInlineScript( "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-TL6R9GR');" );
	}

	/**
	 * Customize the footer links
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
	 * Fix wikitext
	 */
	public static function onParserPreSaveTransformComplete( Parser $parser, string &$text ) {
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

	public static function fixContentPage( $text ) {
		// Append {{Page data}}
		if ( !preg_match( '/{{[Pp]age[_ ]data/', $text ) ) {
			$text .= "\n\n{{Page data}}";
		}

		// Move categories to the bottom
		$count = preg_match_all( '/\[\[ ?[Cc]ategory ?: ?([^]]+) ?\]\]/', $text, $matches );
		if ( $count ) {
			$text .= "\n";
			foreach ( $matches[0] as $match ) {
				$text = str_replace( $match, '', $text );
			}
			$categories = $matches[1];
			$categories = array_unique( $categories );
			foreach ( $categories as $category ) {
				$text .= "\n[[Category:$category]]";
			}
		}

		return $text;
	}

	public static function fixUserPage( $text ) {
		// @todo
		return $text;
	}

	public static function fixCategoryPage( $text ) {
		// @todo
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
	public static function fixFilePage( $text ) {
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
			if ( $license === 'PD-USGOV' ) {
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

	/**
	 * Main hook
	 *
	 * @param Parser $parser Parser object
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'arraymap', [ self::class, 'onFunctionHook' ], Parser::SFH_OBJECT_ARGS );
	}

	/**
	 * This method is copied from Extension:PageForms
	 * but we copy it here rather than enabling the extension
	 * because it's a big extension and this is the only thing we use from it
	 */
	public static function onFunctionHook( Parser $parser, $frame, $args ) {
		// Set variables
		$value = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$delimiter = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : ',';
		$variable = isset( $args[2] ) ? trim( $frame->expand( $args[2], PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES ) ) : 'x';
		$formula = isset( $args[3] ) ? $args[3] : 'x';
		$newDelimiter = isset( $args[4] ) ? trim( $frame->expand( $args[4] ) ) : ', ';
		$conjunction = isset( $args[5] ) ? trim( $frame->expand( $args[5] ) ) : $newDelimiter;

		// Unstrip some
		$delimiter = $parser->getStripState()->unstripNoWiki( $delimiter );

		// Let '\n' represent newlines, and '\s' represent spaces
		$delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $delimiter );
		$newDelimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $newDelimiter );
		$conjunction = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $conjunction );

		// Split by delimiter
		if ( $delimiter == '' ) {
			$valuesArray = preg_split( '/(.)/u', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		} else {
			$valuesArray = explode( $delimiter, $value );
		}

		// Add results to the results array only if the old value was
		// non-null, and the new, mapped value is non-null as well
		$resultsArray = [];
		foreach ( $valuesArray as $oldValue ) {
			$oldValue = trim( $oldValue );
			if ( $oldValue == '' ) {
				continue;
			}
			$resultValue = $frame->expand( $formula, PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES );
			$resultValue = str_replace( $variable, $oldValue, $resultValue );
			$resultValue = $parser->preprocessToDom( $resultValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
			$resultValue = trim( $frame->expand( $resultValue ) );
			if ( $resultValue == '' ) {
				continue;
			}
			$resultsArray[] = $resultValue;
		}

		// Build the result text
		$resultText = '';
		if ( $conjunction != $newDelimiter ) {
			$conjunction = ' ' . trim( $conjunction ) . ' ';
		}
		$numValues = count( $resultsArray );
		for ( $i = 0; $i < $numValues; $i++ ) {
			if ( $i == 0 ) {
				$resultText .= $resultsArray[ $i ];
			} elseif ( $i == $numValues - 1 ) {
				$resultText .= $conjunction . $resultsArray[ $i ];
			} else {
				$resultText .= $newDelimiter . $resultsArray[ $i ];
			}
		}
		return $resultText;
	}
}