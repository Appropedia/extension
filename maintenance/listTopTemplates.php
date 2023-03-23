<?php

/**
 * This maintenance script lists the top templates by use count
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class ListTopTemplates extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'List top templates by use count' );
		$this->addOption( 'limit', 'Max number of templates to show', false, true );
	}

	public function execute() {
        $templates = [];

		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select( 'page', 'page_id', [ 'page_is_redirect' => 0 ] );
		foreach ( $result as $row ) {
			$id = $row->page_id;
			$Title = Title::newFromID( $id );
			if ( ! $Title->exists() ) {
				continue;
			}
			$Page = WikiPage::factory( $Title );
			if ( ! $Page ) {
				continue;
			}
			$Revision = $Page->getRevision();
			if ( ! $Revision ) {
				continue;
			}
			$Content = $Revision->getContent( Revision::RAW );
			if ( ! $Content ) {
				continue;
			}
			$text = ContentHandler::getContentText( $Content );
			if ( ! $text ) {
				continue;
			}

			preg_replace_callback( '/[^{]{{([^#}{|]+)/', function ( $matches ) use ( $templates )  {
				$template = $matches[1];
				$template = trim( $template );
				$template = ucfirst( $template );
				$template = str_replace( '_', ' ', $template );
				$count = $templates[ $template ] ?? 0;
				$count++;
				$templates[ $template ] = $count;
			}, '@' . $text );
		}

        // Sort by use count
		asort( $templates );

        // Trim by limit
		$limit = $this->getOption( 'limit' );
        if ( $limit ) {
    		$templates = array_splice( $templates, 0, $limit );
        }

        // Echo results
		foreach ( $templates as $template => $count ) {
			echo $count . ' ' . $template . PHP_EOL;
		}
	}
}

$maintClass = ListTopTemplates::class;
require_once RUN_MAINTENANCE_IF_MAIN;