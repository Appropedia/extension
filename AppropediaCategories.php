<?php

use MediaWiki\MediaWikiServices;

/**
 * This class adds pages to maintenance categories
 */
class AppropediaCategories {

	public static function onContentAlterParserOutput( Content $content, Title $title, ParserOutput &$output ) {

		// Commas in the title
		$titleText = $title->getText();
		if ( str_contains( $titleText, ',' ) ) {
			$output->addCategory( 'Pages with commas in the title' );
		}

		// Multiple <h1> titles
		$wikitext = $content->getText();
		if ( preg_match( '/\n=[^=]+=\n/', $wikitext ) ) {
			$output->addCategory( 'Pages with more than one title' );
		}

		// Orphan pages
		$parent = $title->getBaseTitle();
		if ( !$parent->exists() ) {
			$output->addCategory( 'Pages with no parent' );
		}

		// Per-namespace rules
		$namespace = $title->getNamespace();
		if ( $namespace === NS_MAIN ) {

			// No lead section
			// @todo

			// No main image
			$pageImage = $output->getPageProperty( 'page_image_free' );
			if ( !$pageImage ) {
				$output->addCategory( 'Pages with no main image' );
			}
	
			// Too long
			$size = $content->getSize();
			if ( $size > 100000 ) {
				$output->addCategory( 'Pages too long' );
			}
	
			// Too short
			if ( $size < 1000 ) {
				$output->addCategory( 'Stubs' );
			}
	
			// Nested templates
			if ( preg_match( '/{{[^}]+{{/', $wikitext ) ) {
				$output->addCategory( 'Pages with nested templates' );
			}

			// Sections nested too deep
			if ( preg_match( '/\n=====+[^=]+=====+\n/', $wikitext ) ) {
				$output->addCategory( 'Pages with sections nested too deep' );
			}

			// Lists nested too deep
			if ( preg_match( '/\n[*#][*#][*#]/', $wikitext ) ) {
				$output->addCategory( 'Pages with lists nested too deep' );
			}

			// Parser functions
			if ( preg_match( '/{{#/', $wikitext ) ) {
				$output->addCategory( 'Pages with parser functions' );
			}

			// Magic words
			if ( preg_match( '/__[A-Z]+?__/', $wikitext ) ) {
				$output->addCategory( 'Pages with magic words' );
			}

			// <references> without <ref>
			if ( preg_match( '/<references/', $wikitext ) && !preg_match( '/<ref[> ]/', $wikitext ) ) {
				$output->addCategory( 'Pages with references tag but no references' );
			}

			// <ref> without <references>
			if ( preg_match( '/<ref[> ]/', $wikitext ) && !preg_match( '/<references/', $wikitext ) ) {
				$output->addCategory( 'Pages with references but no references tag' );
			}
		}
	}
}
