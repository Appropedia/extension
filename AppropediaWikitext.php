<?php

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

		// Prevent infinite loops
		if ( $user->getName() === $wgAppropediaBotAccount ) {
			return;
		}

		// If a user tries to revert an edit done by this script, don't insist
		if ( $editResult->isRevert() ) {
			return;
		}

		// Only for wikitext
		$title = $wikiPage->getTitle();
		$contentModel = $title->getContentModel();
		if ( $contentModel !== CONTENT_MODEL_WIKITEXT ) {
			return;
		}

		// Do general fixes
		$content = $wikiPage->getContent();
		$wikitext = ContentHandler::getContentText( $content );
		$namespace = $wikiPage->getNamespace();
		switch ( $namespace ) {
			case 0:
				$fixed = self::fixContentPage( $wikitext, $title );
				break;
			case 2:
				$fixed = self::fixUserPage( $wikitext, $title );
				break;
			case 6:
				$fixed = self::fixFilePage( $wikitext, $title );
				break;
			case 14:
				$fixed = self::fixCategoryPage( $wikitext, $title );
				break;
		}

		// Check if anything changed
		if ( !self::$fixes ) {
			return;
		}

		// Save the fixed wikitext
		$content = ContentHandler::makeContent( $fixed, $title );
		$user = User::newSystemUser( $wgAppropediaBotAccount );
		$updater = $wikiPage->newPageUpdater( $user );
		$updater->setContent( 'main', $content );
		$summary = implode( ' + ', self::$fixes );
		$comment = CommentStoreComment::newUnsavedComment( $summary);
		$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );
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
		if ( preg_match( "/=={{int:filedesc}}==\n{{Information\n\|description={{en\|1=(.*)}}\n\|date=(.*)\n\|source=(.*)\n\|author=(.*)\n\|permission=(.*)\n\|other versions=(.*)\n}}\n\n=={{int:license-header}}==\n{{(.*)}}\n*(.*)/s", $wikitext, $matches ) ) {

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
			$wikitext = "$description\n\n{{File data";
			foreach ( $params as $param => $value ) {
				$wikitext .= "\n| $param = $value";
			}
			$wikitext .= "\n}}";
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
}
