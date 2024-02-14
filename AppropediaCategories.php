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
			$output->addCategory( 'Pages_with_commas_in_the_title' );
		}

		// Multiple <h1> titles
		$wikitext = $content->getText();
		if ( preg_match( '/\n=[^=]+=\n/', $wikitext ) ) {
			$output->addCategory( 'Pages_with_more_than_one_title' );
		}

		// Orphan pages
		$parent = $title->getBaseTitle();
		if ( !$parent->exists() ) {
			$output->addCategory( 'Pages_with_no_parent' );
		}

		// Per-namespace rules
		$namespace = $title->getNamespace();
		if ( $namespace === NS_MAIN ) {

			// No lead section
			// @todo

			// No main image
			$pageImage = $output->getPageProperty( 'page_image_free' );
			if ( !$pageImage ) {
				$output->addCategory( 'Pages_with_no_main_image' );
			}
	
			// Too long
			$size = $content->getSize();
			if ( $size > 100000 ) {
				$output->addCategory( 'Pages_too_long' );
			}
	
			// Too short
			if ( $size < 1000 ) {
				$output->addCategory( 'Stubs' );
			}
	
			// Nested templates
			if ( preg_match( '/{{[^}]+{{/', $wikitext ) ) {
				$output->addCategory( 'Pages_with_nested_templates' );
			}

			// Sections nested too deep
			if ( preg_match( '/\n=====+[^=]+=====+\n/', $wikitext ) ) {
				$output->addCategory( 'Pages_with_sections_nested_too_deep' );
			}

			// Lists nested too deep
			if ( preg_match( '/\n[*#][*#][*#]/', $wikitext ) ) {
				$output->addCategory( 'Pages_with_lists_nested_too_deep' );
			}

			// Parser functions
			if ( preg_match( '/{{#/', $wikitext ) ) {
				$output->addCategory( 'Pages_with_parser_functions' );
			}

			// Magic words
			if ( preg_match( '/__[A-Z]+?__/', $wikitext ) ) {
				$output->addCategory( 'Pages_with_magic_words' );
			}

			// <references> without <ref>
			if ( preg_match( '/<references/', $wikitext ) && !preg_match( '/<ref[> ]/', $wikitext ) ) {
				$output->addCategory( 'Pages_with_references_tag_but_no_references' );
			}

			// <ref> without <references>
			if ( preg_match( '/<ref[> ]/', $wikitext ) && !preg_match( '/<references/', $wikitext ) ) {
				$output->addCategory( 'Pages_with_references_but_no_references_tag' );
			}
		}
	}
}