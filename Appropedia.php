<?php

class Appropedia {

	/**
	 * Add JS and CSS specific to Appropedia
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModules( 'ext.Appropedia' );
		$out->addModuleStyles( 'ext.Appropedia.styles' );
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
		// @todo
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
	 * because it's the only thing we want from it
	 */
	public static function onFunctionHook( Parser $parser, $frame, $args ) {
		// Set variables
		$value = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$delimiter = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : ',';
		$var = isset( $args[2] ) ? trim( $frame->expand( $args[2], PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES ) ) : 'x';
		$formula = isset( $args[3] ) ? $args[3] : 'x';
		$new_delimiter = isset( $args[4] ) ? trim( $frame->expand( $args[4] ) ) : ', ';
		$conjunction = isset( $args[5] ) ? trim( $frame->expand( $args[5] ) ) : $new_delimiter;
		// Unstrip some
		$delimiter = $parser->getStripState()->unstripNoWiki( $delimiter );
		// Let '\n' represent newlines, and '\s' represent spaces
		$delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $delimiter );
		$new_delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $new_delimiter );
		$conjunction = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $conjunction );

		if ( $delimiter == '' ) {
			$values_array = preg_split( '/(.)/u', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		} else {
			$values_array = explode( $delimiter, $value );
		}
		$results_array = [];
		// Add results to the results array only if the old value was
		// non-null, and the new, mapped value is non-null as well
		foreach ( $values_array as $old_value ) {
			$old_value = trim( $old_value );
			if ( $old_value == '' ) {
				continue;
			}
			$result_value = $frame->expand( $formula, PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES );
			$result_value = str_replace( $var, $old_value, $result_value );
			$result_value = $parser->preprocessToDom( $result_value, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
			$result_value = trim( $frame->expand( $result_value ) );
			if ( $result_value == '' ) {
				continue;
			}
			$results_array[] = $result_value;
		}
		if ( $conjunction != $new_delimiter ) {
			$conjunction = " " . trim( $conjunction ) . " ";
		}
		$result_text = "";
		$num_values = count( $results_array );
		for ( $i = 0; $i < $num_values; $i++ ) {
			if ( $i == 0 ) {
				$result_text .= $results_array[$i];
			} elseif ( $i == $num_values - 1 ) {
				$result_text .= $conjunction . $results_array[$i];
			} else {
				$result_text .= $new_delimiter . $results_array[$i];
			}
		}
		return $result_text;
	}
}