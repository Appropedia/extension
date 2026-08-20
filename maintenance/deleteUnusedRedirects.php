<?php

/**
 * This maintenance script deletes all broken redirects
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class DeleteUnusedRedirects extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Delete unused redirects' );
		$this->addOption( 'delete', 'Actually do the deletions' );
	}

	public function execute() {

		// Get all the redirects
		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
		$results = $dbr->newSelectQueryBuilder()
			->fields( [ 'rd_from', 'rd_namespace', 'rd_title' ] )
			->from( 'redirect' )
			->where( [
				'rd_namespace > -1', // Exclude special pages
				'rd_interwiki IS NULL OR rd_interwiki = ""', // Exclude interwiki links
			] )
			->fetchResultSet();

		// Set some variables outside the loop
		$delete = $this->getOption( 'delete' );
		$backlinkCacheFactory = $services->getBacklinkCacheFactory();
		$wikiPageFactory = $services->getWikiPageFactory();
		$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );

		// Delete the ones that are not linked from anywhere
		foreach ( $results as $result ) {
			$title = Title::newFromID( $result->rd_from );
			$titleText = $title->getFullText();
			if ( str_starts_with( $titleText, 'TissueDB' ) ) {
				continue;
			}
			$backlinkCache = $backlinkCacheFactory->getBacklinkCache( $title );
			if ( $title->getNamespace() === NS_FILE ) {
				$links = $backlinkCache->hasLinks( 'imagelinks' );
			} else {
				$links = $backlinkCache->hasLinks( 'pagelinks' );
			}
			if ( $links ) {
				continue;
			}
			$url = $title->getFullUrl();
			$this->output( $url );
			if ( $delete ) {
				$target = Title::newFromText( $result->rd_title, $result->rd_namespace );
				$targetText = $target->getFullText();
				$page = $wikiPageFactory->newFromTitle( $title );
				$page->doDeleteArticleReal( "Unused redirect to [[$targetText]]", $user );
				$this->output( ' .. deleted!' );
			}
			$this->output( PHP_EOL );

			//break; // Uncomment to debug
		}
	}
}

$maintClass = DeleteUnusedRedirects::class;
require_once RUN_MAINTENANCE_IF_MAIN;