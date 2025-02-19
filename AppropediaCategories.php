<?php

use MediaWiki\MediaWikiServices;

/**
 * This class adds pages to maintenance categories
 */
class AppropediaCategories {

	public static function onContentAlterParserOutput( Content $content, Title $title, ParserOutput &$output ) {

		// Ignore redirects
		if ( $title->isRedirect() ) {
			return;
		}

		// The main page is always an exception
		if ( $title->isMainPage() ) {
			return;
		}

		// Multiple <h1> titles
		$wikitext = $content->getText();
		if ( preg_match( '/^=[^=]+=$/m', $wikitext ) ) {
			$output->addCategory( 'Pages_with_more_than_one_title' );
		}

		// Commas in the title
		$titleText = $title->getText();
		if ( str_contains( $titleText, ',' ) ) {
			$output->addCategory( 'Pages_with_commas_in_the_title' );
		}

		// No real content
		if ( !trim( $wikitext ) ) {
			$output->addCategory( 'Empty_pages' );
		}

		// Orphan pages
		$parent = $title->getBaseTitle();
		if ( !$parent->equals( $title ) && !$parent->exists() ) {
			$output->addCategory( 'Pages_with_no_parent' );
		}

		// Most rules don't apply to automatic translations
		$categories = $output->getCategoryNames();
		if ( in_array( 'Automatic_translations', $categories ) ) {
			return;
		}

		// Get the lead text
		$match = preg_match( '/(.*?)^=/ms', $wikitext, $matches );
		$lead = $match ? $matches[1] : $wikitext;
		while ( preg_match( '/{{.*?}}/s', $lead ) ) {
			$lead = preg_replace( '/{{.*?}}/s', '', $lead ); // Remove templates (fails with nested templates)
		}
		while ( preg_match( '/\[\[File:.*?\]\]/s', $lead ) ) {
			$lead = preg_replace( '/\[\[File:.*?\]\]/s', '', $lead ); // Remove files (fails with nested links)
		}
		$lead = trim( $lead );

		// Per-namespace rules
		$namespace = $title->getNamespace();
		switch ( $namespace ) {

			case NS_MAIN:

				// No lead text
				if ( !$lead && !$title->isSubpage() ) {
					$output->addCategory( 'Pages_with_no_lead_text' );
				}

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

				break;

			case NS_CATEGORY:

				// Too much text
				$size = $content->getSize();
				if ( $size > 1000 ) {
					$output->addCategory( 'Categories_with_too_much_text' );
				}

				// Self-contained categories
				if ( in_array( str_replace( ' ', '_', $titleText ), $categories ) ) {
					$output->addCategory( 'Categories_that_contain_themselves' );
				}

				break;
		}

		if ( $title->isTalkPage() ) {

			// Talk pages with no subject page
			$subject = $title->getSubjectPage();
			if ( !$subject->exists() ) {
				$output->addCategory( 'Talk_pages_with_no_subject_page' );
			}

			// Talk pages with lead text
			if ( $lead ) {
				$output->addCategory( 'Talk_pages_with_lead_text' );
			}
		}
	}
}
