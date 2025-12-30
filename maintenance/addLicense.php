<?php

/**
 * This maintenance script adds the license to all pages that don't have it already
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class AddLicense extends Maintenance {

	public function execute() {

		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
		$ids = $dbr->newSelectQueryBuilder()
			->field( 'page_id' )
			->from( 'page' )
			->where( [ 'page_is_redirect' => 0, 'page_namespace' => 0 ] )
			->fetchFieldValues();

		$count = 0;
		$total = count( $ids );
		$factory = $services->getWikiPageFactory();
		foreach ( $ids as $id ) {
			$count++;

			// Subpages inherit their license so we don't need to set it
			$title = Title::newFromID( $id );
			if ( $title->isSubpage() ) {
				continue;
			}

			// In 2019 we changed our license from CC-BY-SA-3.0 to CC-BY-SA-4.0
			$revision = $services->getRevisionLookup()->getFirstRevision( $title );
			$timestamp = $revision->getTimestamp();
			$year = substr( $timestamp, 0, 4 );
			$year = intval( $year );
			if ( $year > 2019 ) {
				continue;
			}

			// Check if the page already has a license set
			$page = $factory->newFromTitle( $title );
			$revision = $page->getRevisionRecord();
			$content = $revision->getContent( 'main' );
			$text = $content->getText();
			$text = trim( $text );
			if ( preg_match( '/{{Page data[^}]*\| *license *= *.+/', $text ) ) {
				continue;
			}

			// Edit the wikitext
			if ( preg_match( '/{{Page data}}/', $text ) ) {
				$text = preg_replace( '/{{Page data}}/', "{{Page data\n| license = CC-BY-SA-3.0\n}}", $text );
			} else if ( preg_match( '/{{Page data/', $text ) ) {
				$text = preg_replace( '/{{Page data/', "{{Page data\n| license = CC-BY-SA-3.0", $text );
			} else if ( preg_match( '/\[\[Category:.*\]\]$/s', $text ) ) {
				$text = preg_replace( '/\[\[Category:.*\]\]$/s', "{{Page data\n| license = CC-BY-SA-3.0\n}}\n\n$0", $text );
			} else {
				$text .= "\n\n{{Page data\n| license = CC-BY-SA-3.0\n}}";
			}

			// Output the progress
			$url = $title->getFullURL();
			$percent = round( $count / $total * 100, 2 );
			$this->output( "$percent%	$url" . PHP_EOL );

			// Save the page
			$content = ContentHandler::makeContent( $text, $title );
			$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
			$updater = $page->newPageUpdater( $user );
			$updater->setContent( 'main', $content );
			$comment = CommentStoreComment::newUnsavedComment( 'Add license' );
			$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );
		}
	}
}

$maintClass = AddLicense::class;
require_once RUN_MAINTENANCE_IF_MAIN;