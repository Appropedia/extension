<?php

/**
 * This script generates the kiwix.tsv file used by Kiwix for scraping Appropedia
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class GenerateKiwixList extends Maintenance {

	public function execute() {
		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
		$config = $this->getConfig();

		// Figure out the private categories from Extension:CategoryLockdown
		$categoryLockdown = $config->get( 'CategoryLockdown' );
		$privateCategories = [];
		foreach ( $categoryLockdown as $category => $permissions ) {
			if ( isset( $permissions['read'] ) ) {
				$privateCategories[] = str_replace( ' ', '_', $category );
			}
		}
		$privateCategories = implode( '","', $privateCategories );

		// Build the query
		$tablePrefix = $dbr->tablePrefix();
		$query = $dbr->newSelectQueryBuilder();
		$query->field( 'page_title' );
		$query->from( 'page' );
		$query->where( [
			'page_namespace' => NS_MAIN,
			'page_is_redirect' => 0,
			'page_id NOT IN ( SELECT cl_from FROM ' . $tablePrefix . 'categorylinks WHERE cl_to IN ("' . $privateCategories . '") )'
		] );
		$results = $query->fetchResultSet();

		// Make the TSV file
		$titles = [];
		foreach ( $results as $result ) {
			$titles[] = $result->page_title;
		}
		$titles = implode( PHP_EOL, $titles );
		$file = '/home/appropedia/public_html/kiwix.tsv';
		$contents = "\xEF\xBB\xBF" . $titles; // Add BOM UTF8, see https://stackoverflow.com/questions/4839402
		file_put_contents( $file, $contents );
	}
}

$maintClass = GenerateKiwixList::class;
require_once RUN_MAINTENANCE_IF_MAIN;
