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

class AddPageData extends Maintenance {

	public function execute() {

		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select( 'page', 'page_id', [ 'page_namespace' => 0, 'page_is_redirect' => 0 ] );
		foreach ( $result as $row ) {
			$id = $row->page_id;
			$Title = Title::newFromID( $id );
			if ( ! $Title->exists() ) {
				continue;
			}
			if ( $Title->isMainPage() ) {
				continue;
			}
			$Page = WikiPage::factory( $Title );
			$Revision = $Page->getRevision();
			$Content = $Revision->getContent( Revision::RAW );
			$text = ContentHandler::getContentText( $Content );

			if ( strpos( $text, '{{Page data' ) !== false ) {
				continue;
			}

			$this->output( $Title->getFullURL() . PHP_EOL );

			if ( preg_match( '/^\[\[[^]]+\]\]/', $text ) ) {
				$text = preg_replace( '/^\[\[[^]]+\]\]/', "$0\n\n{{Page data}}", $text );
			} else if ( preg_match( '/^{{[^]]+}}/', $text ) ) {
				$text = preg_replace( '/^{{[^]]+}}/', "$0\n\n{{Page data}}", $text );
			} else {
				$text = "{{Page data}}\n\n" . $text;
			}

			// Save the page
			$Content = ContentHandler::makeContent( $text, $Title );
			$User = User::newSystemUser( 'Page script' );
			$Updater = $Page->newPageUpdater( $User );
			$Updater->setContent( 'main', $Content );
			$Updater->saveRevision( CommentStoreComment::newUnsavedComment( 'Add [[Template:Page data]]' ), EDIT_SUPPRESS_RC );
			//break;
		}
	}
}

$maintClass = AddPageData::class;
require_once RUN_MAINTENANCE_IF_MAIN;