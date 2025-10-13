<?php

use MediaWiki\MediaWikiServices;

/**
 * This class fixes the wikitext of some pages
 * according to Appropedia standards for each namespace
 */
class AppropediaWikitext {

	/**
	 * This array will contain the fixes actually performed
	 * in order to generate an informative edit summary
	 */
	public static $fixes = [];

	/**
	 * This hook fires after each page save
	 * and triggers the fixes that will be performed by a bot account
	 */
	public static function onPageSaveComplete( WikiPage $wikiPage, MediaWiki\User\UserIdentity $user, string $summary, int $flags, MediaWiki\Revision\RevisionRecord $revisionRecord, MediaWiki\Storage\EditResult $editResult ) {
		global $wgAppropediaBotAccount;

		// The main page is always an exception
		$title = $wikiPage->getTitle();
		if ( $title->isMainPage() ) {
			return;
		}

		// Prevent infinite loops
		if ( $user->getName() === $wgAppropediaBotAccount ) {
			return;
		}

		// If a user reverts an edit done by this script, don't insist
		if ( $editResult->isRevert() ) {
			return;
		}

		// Only for wikitext
		$contentModel = $title->getContentModel();
		if ( $contentModel !== CONTENT_MODEL_WIKITEXT ) {
			return;
		}

		// Don't fix redirects
		if ( $title->isRedirect() ) {
			return;
		}

		// Do the fixes
		$content = $wikiPage->getContent();
		$wikitext = $content->getText();
		$fixed = self::fixWikitext( $wikitext, $title );

		// Check if anything changed
		if ( !self::$fixes ) {
			return;
		}

		// Save the fixed wikitext
		AppropediaWikitext::saveWikitext( $fixed, $wikiPage );
	}

	/**
	 * Fix the given wikitext
	 */
	public static function fixWikitext( $wikitext, $title ) {
		$namespace = $title->getNamespace();
		switch ( $namespace ) {
			case NS_MAIN:
				return self::fixContentPage( $wikitext, $title );
			case NS_USER:
				return self::fixUserPage( $wikitext, $title );
			case NS_FILE:
				return self::fixFilePage( $wikitext, $title );
			case NS_CATEGORY:
				return self::fixCategoryPage( $wikitext, $title );
		}
	}
	
	/**
	 * Fix the wikitext of a content page
	 */
	public static function fixContentPage( $wikitext, $title ) {
		// Append {{Page data}}
		if ( !preg_match( '/{{[Pp]age[_ ]data/', $wikitext )
			&& !preg_match( '/^{{Automatic translation notice/', $wikitext ) // except automatic translations
			&& !preg_match( '/^#(.+ ?\[\[.+\]\])/', $wikitext ) // and redirects
		) {
			$wikitext .= "\n\n{{Page data}}";
			self::$fixes[] = 'Add [[Template:Page data]]';
		}

		return $wikitext;
	}

	/**
	 * Fix the wikitext of a user page
	 */
	public static function fixUserPage( $wikitext, $title ) {
		if ( $title->isSubpage() ) {
			return;
		}

		// Prepend {{User data}}
		if ( !preg_match( '/{{[Uu]ser[_ ]data/', $wikitext ) ) {
			$wikitext = "{{User data}}\n\n$wikitext";
			self::$fixes[] = 'Add [[Template:User data]]';
		}
		return $wikitext;
	}

	/**
	 * Fix the wikitext of a category page
	 */
	public static function fixCategoryPage( $wikitext, $title ) {
		// Prepend {{Category data}}
		if ( !preg_match( '/{{[Cc]ategory[_ ]data/', $wikitext ) ) {
			$wikitext = "{{Category data}}\n\n$wikitext";
			self::$fixes[] = 'Add [[Template:Category data]]';
		}
		return $wikitext;
	}

	/**
	 * Fix the wikitext of a file page
	 */
	public static function fixFilePage( $wikitext, $title ) {
		// This ugly contraption is here because Extension:UploadWizard has hard-coded
		// the structure of the file pages it creates, so we can't modify them via config
		// Therefore, we check every single page save and if it has the structure of
		// a file page created by Upload Wizard, we transform it to our preferred structure
		if ( preg_match( "/=={{int:filedesc}}==\n{{Information\n\|description={{[a-z]+\|1= *(?<description>.*) *}}\n\|date=(?<date>.*)\n\|source=(?<source>.*)\n\|author=(?<author>.*)\n\|permission=(?<permission>.*)\n\|other versions=(?<otherVersions>.*)\n}}\n\n=={{int:license-header}}==\n{{(?<license>.*)}}\n*(?<categories>.*)/s", $wikitext, $matches ) ) {

			// Get data
			$description = $matches['description'];
			$date = $matches['date'];
			$source = $matches['source'];
			$author = $matches['author'];
			$permission = $matches['permission'];
			$otherVersions = $matches['otherVersions'];
			$license = $matches['license'];
			$categories = $matches['categories'];

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
			if ( $license === 'CC-ZERO' ) {
				$license = 'CC0-1.0';
			}
			if ( $license === 'PD' ) {
				$license = 'Public domain';
			}
			if ( $license === 'PD-US' ) {
				$license = 'Public domain';
			}
			if ( $license === 'PD-USGOV' ) {
				$license = 'Public domain';
			}
			if ( $license === 'FAIR USE' ) {
				$license = 'Fair use';
			}

			$params = [
				'date' => $date,
				'author' => $author,
				'source' => $source,
				'license' => $license,
			];
			$params = array_filter( $params );

			// Build wikitext
			$wikitext = "$description\n\n{{File data";
			foreach ( $params as $param => $value ) {
				$wikitext .= "\n| $param = $value";
			}
			$wikitext .= "\n}}";
			if ( $categories ) {
				$wikitext .= "\n\n$categories";
			}
			self::$fixes[] = 'Fix file page';
		}

		// Fix file pages created with the visual editor
		// Most is done from LocalSettings.php
		// here we just delink the author
		if ( preg_match( '/\| author = \[\[([^|]+)\|[^]]+\]\]/', $wikitext ) ) {
			$wikitext = preg_replace( '/\| author = \[\[([^|]+)\|[^]]+\]\]/', '| author = $1', $wikitext );
			self::$fixes[] = 'Delink author';
		}

		// Fix file pages created via Special:Upload
		if ( preg_match( "/== Summary ==\n(.*)\n== Licensing ==\n{{(.*)}}/s", $wikitext, $matches ) ) {
			$description = trim( $matches[1] );
			$license = $matches[2];
			$wikitext = "$description\n\n{{File data\n| license = $license\n}}";
			self::$fixes[] = 'Fix file page';
		}

		// Fix empty file pages
		if ( !preg_match( '/{{[Ff]ile[_ ]data/', $wikitext ) ) {
			$wikitext .= "\n\n{{File data}}";
			self::$fixes[] = 'Add [[Template:File data]]';
		}

		return $wikitext;
	}

	/**
	 * @param string $wikitext
	 * @param WikiPage $wikiPage
	 * @return void
	 */
	public static function saveWikitext( string $wikitext, WikiPage $wikiPage ) {
		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		$account = $config->get( 'AppropediaBotAccount' );
		$user = User::newSystemUser( $account );
		$updater = $wikiPage->newPageUpdater( $user );
		$title = $wikiPage->getTitle();
		$content = ContentHandler::makeContent( $wikitext, $title );
		$updater->setContent( 'main', $content );
		$summary = implode( ' + ', self::$fixes );
		$comment = CommentStoreComment::newUnsavedComment( $summary );
		$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );
	}
}
