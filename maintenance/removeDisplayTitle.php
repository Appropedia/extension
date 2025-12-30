<?php

/**
 * This maintenance script removes display titles that are identical to the real title
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

		$count = 0;
		$total = count( $ids );
		$factory = $services->getWikiPageFactory();
		foreach ( $ids as $id ) {
			$count++;

			$title = Title::newFromID( $id );
			$url = $title->getFullURL();
			$page = $factory->newFromTitle( $title );
			$revision = $page->getRevisionRecord();
			$content = $revision->getContent( 'main' );
			$text = $content->getText();
			if ( !preg_match( '/{{Page data[^}]*\| *title *= *(.+)/', $text, $matches ) ) {
				continue;
			}
			$displayTitle = $matches[1];
			$subpageText = $title->getSubpageText();
			if ( $displayTitle != $subpageText ) {
				continue;
			}
			$text = preg_replace( '/{{Page data([^}]*)\| *title *= *(.+)\n/', '{{Page data$1', $text );

			// Output the progress
			$percent = round( $count / $total * 100, 2 );
			$this->output( "$percent%	$url" . PHP_EOL );

			// Save the page
			$content = ContentHandler::makeContent( $text, $title );
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