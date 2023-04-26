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