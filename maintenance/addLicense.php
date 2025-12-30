<?php

/**
 * This maintenance script adds a license to {{Page data}} according to the year when the page was created
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

		$total = count( $ids );
		$factory = $services->getWikiPageFactory();
		foreach ( $ids as $count => $id ) {

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
			$wikitext = $content->getText();
			$wikitext = trim( $wikitext );
			if ( preg_match( '/{{Page data[^}]*\| *license *= *.+/', $wikitext ) ) {
				continue;
			}

			// Edit the wikitext
			if ( preg_match( '/{{Page data}}/', $wikitext ) ) {
				$wikitext = preg_replace( '/{{Page data}}/', "{{Page data\n| license = CC-BY-SA-3.0\n}}", $wikitext );
			} else if ( preg_match( '/{{Page data/', $wikitext ) ) {
				$wikitext = preg_replace( '/{{Page data/', "{{Page data\n| license = CC-BY-SA-3.0", $wikitext );
			} else if ( preg_match( '/\[\[Category:.*\]\]$/s', $wikitext ) ) {
				$wikitext = preg_replace( '/\[\[Category:.*\]\]$/s', "{{Page data\n| license = CC-BY-SA-3.0\n}}\n\n$0", $wikitext );
			} else {
				$wikitext .= "\n\n{{Page data\n| license = CC-BY-SA-3.0\n}}";
			}

			// Output the progress
			$url = $title->getFullURL();
			$percent = round( $count / $total * 100, 2 );
			$this->output( "$percent%	$url" . PHP_EOL );

			// Save the page
			$content = ContentHandler::makeContent( $wikitext, $title );
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