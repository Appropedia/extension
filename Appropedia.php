<?php

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\StoreFactory;

/**
 * This class contains all code not covered by more specific classes
 */
class Appropedia {

	/**
	 * Add JS and CSS specific to Appropedia
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModules( 'ext.Appropedia' );
		$out->addModuleStyles( 'ext.Appropedia.styles' );
		$out->addLink( [ 'rel' => 'manifest', 'href' => '/manifest.json' ] );
		$out->addLink( [ 'rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => '/logos/favicon-32x32.png' ] );
		$out->addLink( [ 'rel' => 'icon', 'type' => 'image/png', 'sizes' => '16x16', 'href' => '/logos/favicon-16x16.png' ] );
		self::setTitleTags( $out, $skin );
	}

	/**
	 * Set or refine the title meta tags for SEO purposes
	 */
	public static function setTitleTags( OutputPage $out, Skin $skin ) {
		// If the 'Title tag' semantic property is set, just use it and be done
		$title = $skin->getTitle();
		if ( $title->isContentPage() ) {
			$property = DIProperty::newFromUserLabel( 'Title tag' );
			$subject = DIWikiPage::newFromText( $title );
			$store = StoreFactory::getStore();
			$data = $store->getSemanticData( $subject );
			$values = $data->getPropertyValues( $property );
			if ( $values ) {
				$value = array_shift( $values );
				$titleTag = $value->getString();
				$out->setHTMLTitle( $titleTag );
				$out->addMeta( 'title', $titleTag );
				$out->addMeta( 'og:title', $titleTag );
				return;
			}
		}

		// Set the <meta name="title"> and <meta name="og:title"> tags
		$pageTitle = $out->getPageTitle();
		$pageTitle = strip_tags( $pageTitle );
		$out->addMeta( 'title', $pageTitle );
		$out->addMeta( 'og:title', $pageTitle );

		// If the default <title> tag is too long, remove "Appropedia, the sustainability wiki"
		$htmlTitle = $out->getHTMLTitle();
		if ( strlen( $htmlTitle ) > 65 ) {
			$out->setHTMLTitle( $pageTitle );
		}
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
	 * #arraymap parser function
	 *
	 * This method is copied from Extension:PageForms
	 * but we put it here rather than enabling the extension
	 * because it's a big extension and this is the only thing we use from it
	 *
	 * @param Parser $parser Parser object
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'arraymap', function ( Parser $parser, $frame, $args ) {
			$value = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
			$delimiter = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : ',';
			$variable = isset( $args[2] ) ? trim( $frame->expand( $args[2], PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES ) ) : 'x';
			$formula = isset( $args[3] ) ? $args[3] : 'x';
			$newDelimiter = isset( $args[4] ) ? trim( $frame->expand( $args[4] ) ) : ', ';
			$conjunction = isset( $args[5] ) ? trim( $frame->expand( $args[5] ) ) : $newDelimiter;
			$delimiter = $parser->getStripState()->unstripNoWiki( $delimiter );
			$delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $delimiter );
			$newDelimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $newDelimiter );
			$conjunction = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $conjunction );
			if ( $delimiter == '' ) {
				$valuesArray = preg_split( '/(.)/u', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
			} else {
				$valuesArray = explode( $delimiter, $value );
			}
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
		}, Parser::SFH_OBJECT_ARGS );
	}
}