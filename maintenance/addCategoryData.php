<?php

/**
 * This maintenance script adds {{Page data}} to all content pages that don't have it already
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class AddCategoryData extends Maintenance {

	public function execute() {

		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select( 'page', 'page_id', [ 'page_namespace' => 14, 'page_is_redirect' => 0 ] );
		foreach ( $result as $row ) {
			$id = $row->page_id;
			$Title = Title::newFromID( $id );
			if ( ! $Title->exists() ) {
				continue;
			}
			$Page = WikiPage::factory( $Title );
			$Revision = $Page->getRevisionRecord();
			$Content = $Revision->getContent( 'main' );
			$text = ContentHandler::getContentText( $Content );

			if ( preg_match( '/{{[Cc]ategory[_ ]data/', $text ) ) {
				continue;
			}

			$title = $Title->getFullURL();
			$this->output( $title . PHP_EOL );

			if ( preg_match( '/^{{[^}]+}}/', $text ) ) {
				$text = preg_replace( '/^{{[^}]+}}/', "$0\n\n{{Category data}}", $text );
			} else {
				$text = "{{Category data}}\n\n" . $text;
			}

			// Save the page
			$Content = ContentHandler::makeContent( $text, $Title );
			$User = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
			$Updater = $Page->newPageUpdater( $User );
			$Updater->setContent( 'main', $Content );
			$Updater->saveRevision( CommentStoreComment::newUnsavedComment( 'Add [[Template:Category data]]' ), EDIT_SUPPRESS_RC );
			break;
		}
	}
}

$maintClass = AddCategoryData::class;
require_once RUN_MAINTENANCE_IF_MAIN;