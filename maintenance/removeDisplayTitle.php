<?php

/**
 * This maintenance script removes the "title" parameter from {{Page data}} when it is identical to the real title of the page
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class RemoveDisplayTitle extends Maintenance {

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

			// Check if the page has a display title set
			$title = Title::newFromID( $id );
			$page = $factory->newFromTitle( $title );
			$revision = $page->getRevisionRecord();
			$content = $revision->getContent( 'main' );
			$wikitext = $content->getText();
			if ( !preg_match( '/{{Page data[^}]*\| *title *= *(.+) */', $wikitext, $matches ) ) {
				continue;
			}

			// Check if the display title is equal to the real title
			$displayTitle = $matches[1];
			$subpageText = $title->getSubpageText();
			if ( strtolower( $displayTitle ) != strtolower( $subpageText ) ) {
				continue;
			}

			// Edit the wikitext
			$wikitext = preg_replace( '/{{Page data([^}]*)\| *title *= *(.+)\n/', '{{Page data$1', $wikitext );

			// Output the progress
			$url = $title->getFullURL();
			$percent = round( $count / $total * 100, 2 );
			$this->output( "$percent%	$url" . PHP_EOL );

			// Save the page
			$content = ContentHandler::makeContent( $wikitext, $title );
			$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
			$updater = $page->newPageUpdater( $user );
			$updater->setContent( 'main', $content );
			$comment = CommentStoreComment::newUnsavedComment( 'Remove redundant display title' );
			$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );
		}
	}
}

$maintClass = RemoveDisplayTitle::class;
require_once RUN_MAINTENANCE_IF_MAIN;