<?php

class Appropedia {

	/**
	 * Add JS and CSS specific to Appropedia
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModules( 'ext.Appropedia' );
		$out->addModuleStyles( 'ext.Appropedia.styles' );
		$out->addLink( [ 'rel' => 'manifest', 'href' => '/logos/site.webmanifest' ] );
		$out->addLink( [ 'rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => '/logos/favicon-32x32.png' ] );
		$out->addLink( [ 'rel' => 'icon', 'type' => 'image/png', 'sizes' => '16x16', 'href' => '/logos/favicon-16x16.png' ] );
		$out->addLink( [ 'rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/logos/apple-touch-icon.png' ] );
		self::addGoogleTagManager( $out, $skin );
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
	 * Customize the logo
	 *
	 * This hook changes the logo depending on the category
	 * so we can offer extra branding to some projects
	 * such as https://www.appropedia.org/SELF
	 */
	public static function onPonchoLogo( &$logo, $poncho ) {
		global $wgLogos;

		$title = $poncho->getSkin()->getTitle();
		$categories = $title->getParentCategories();
		$categories = array_keys( $categories );
		if ( in_array( 'Category:SELF', $categories ) ) {
			$subwikiLogo = '/w/images/e/ef/SELF_Logo.png';
			$subwikiPage = 'Surgical Education Learners Forum';
		}
		if ( in_array( 'Category:Fashion_Revolution', $categories ) ) {
			$subwikiLogo = '/w/images/7/7a/Fashion_Revolution_Logo.svg';
			$subwikiPage = 'Fashion Revolution';
		}

		if ( $subwikiLogo ) {
			// Make Appropedia logo
			$appropediaIconAttrs = [ 'src' => $wgLogos['icon'], 'width' => 42, 'height' => 42 ];
			$appropediaIcon = Html::rawElement( 'img', $appropediaIconAttrs );
			$appropediaLogoAttrs = Linker::tooltipAndAccesskeyAttribs( 'p-logo' );
			$appropediaLogoAttrs['id'] = 'appropedia-logo';
			$appropediaLogoAttrs['href'] = htmlspecialchars( $poncho->data['nav_urls']['mainpage']['href'] );
			$appropediaLogo = Html::rawElement( 'a', $appropediaLogoAttrs, $appropediaIcon );

			// Make subwiki logo
			$subwikiIconAttrs = [ 'src' => $subwikiLogo, 'height' => 42 ];
			$subwikiIcon = Html::rawElement( 'img', $subwikiIconAttrs );
			$subwikiTitle = Title::newFromText( $subwikiPage );
			$subwikiLogoAttrs = [ 'id' => 'subwiki-logo', 'title' => $subwikiPage, 'href' => $subwikiTitle->getFullUrl() ];
			$subwikiLogo = Html::rawElement( 'a', $subwikiLogoAttrs, $subwikiIcon );

			// Make the composite logo
			$separator = Html::rawElement( 'span', [ 'id' => 'appropedia-logo-separator' ] );
			$logoAttrs = [ 'id' => 'appropedia-logo-wrapper' ];
			$logo = Html::rawElement( 'div', $logoAttrs, $appropediaLogo . $separator . $subwikiLogo );
		}
	}

	/**
	 * Customize the menu of admins
	 * by replacing SemanticMediaWiki's useless admin links (hidden via CSS)
	 * for Appropedia's awesome Appropedia:Admin_panel
 	 */
	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skinTemplate, array &$links ) {
		$user = $skinTemplate->getUser();
		$groups = $user->getGroups();
		if ( in_array( 'sysop', $groups ) ) {
			$link = [
				'href' => '/Appropedia:Admin_panel',
				'text' => wfMessage( 'appropedia-admin-panel' )->text()
			];
			array_splice( $links['user-menu'], 3, 0, [ $link ] );
		}
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
	 * Main hook
	 *
	 * @param Parser $parser Parser object
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'arraymap', [ self::class, 'onFunctionHook' ], Parser::SFH_OBJECT_ARGS );
	}

	/**
	 * This method is copied from Extension:PageForms
	 * but we put it here rather than enabling the extension
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