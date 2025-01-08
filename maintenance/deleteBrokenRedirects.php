<?php

/**
 * Delete broken redirects
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class DeleteBrokenRedirects extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Delete broken redirects' );
		$this->addOption( 'delete', 'Actually do the deletions' );
	}
	
	public function execute() {
		// Get all the redirects
		$services = MediaWikiServices::getInstance();
		$lb = $services->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		$results = $dbr->newSelectQueryBuilder()
			->select( [ 'rd_from', 'rd_namespace', 'rd_title' ] )
			->from( 'redirect' )
			->where( [
				'rd_namespace > -1', // Exclude special pages
				'rd_interwiki IS NULL OR rd_interwiki = ""', // Exclude interwiki links
			] )
			->fetchResultSet();

		// Delete the ones that point to a page that doesn't exist
		$delete = $this->getOption( 'delete' );
		$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		foreach ( $results as $result ) {
			$target = Title::newFromText( $result->rd_title, $result->rd_namespace );
			if ( $target->exists() ) {
				continue;
			}
			$title = Title::newFromID( $result->rd_from );
			$url = $title->getFullUrl();
			$this->output( $url );
			if ( $delete ) {
				$factory = $services->getWikiPageFactory();
				$page = $factory->newFromTitle( $title );
				$page->doDeleteArticleReal( 'Broken redirect', $user );
				$this->output( ' .. deleted!' );
			}
			$this->output( PHP_EOL );
		}
	}
}

$maintClass = DeleteBrokenRedirects::class;
require_once RUN_MAINTENANCE_IF_MAIN;