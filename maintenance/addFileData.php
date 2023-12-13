<?php

/**
 * This maintenance script adds {{File data}} to all file pages that don't have it already
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
		$result = $dbr->select( 'page', 'page_id', [ 'page_namespace' => 6, 'page_is_redirect' => 0 ] );
		foreach ( $result as $row ) {
			$id = $row->page_id;
			$Title = Title::newFromID( $id );
			if ( ! $Title->exists() ) {
				continue;
			}
			$Page = WikiPage::factory( $Title );
			$Revision = $Page->getRevisionRecord();
      if ( ! $Revision ) {
        continue;
      }
			$Content = $Revision->getContent( 'main' );
			$text = ContentHandler::getContentText( $Content );

			if ( preg_match( '/{{[Ff]ile[_ ]data/', $text ) ) {
				continue;
			}

			$title = $Title->getFullURL();
			$this->output( $title . PHP_EOL );

			if ( preg_match( '/\[\[[Cc]ategory:.*\]\]$/s', $text ) ) {
				$text = preg_replace( '/\[\[[Cc]ategory:.*\]\]$/s', "{{File data}}\n\n$0", $text );
				$this->output( '^ Has categories'. PHP_EOL );
			} else {
				if ( $text ) {
					$this->output( '^ Has text'. PHP_EOL );
				}
				$text = trim( $text . "\n\n{{File data}}" );
			}

			// Save the page
			$Content = ContentHandler::makeContent( $text, $Title );
			$User = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
			$Updater = $Page->newPageUpdater( $User );
			$Updater->setContent( 'main', $Content );
			$Updater->saveRevision( CommentStoreComment::newUnsavedComment( 'Add [[Template:File data]]' ), EDIT_SUPPRESS_RC );
			//break;
		}
	}
}

$maintClass = AddCategoryData::class;
require_once RUN_MAINTENANCE_IF_MAIN;