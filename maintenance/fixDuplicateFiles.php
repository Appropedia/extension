<?php

/**
 * Fix duplicate files
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class AddCategoryData extends Maintenance {

	public function execute() {

		// Get files with duplicates
		$services = MediaWikiServices::getInstance();
		$lb = $services->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		$result = $dbr->select( 'image', [ 'namespace' => NS_FILE, 'title' => 'MIN(img_name)', 'value' => 'count(*)', 'hash' => 'img_sha1' ], [], __METHOD__, [ 'GROUP BY' => 'img_sha1', 'HAVING' => 'count(*) > 1' ] );
		foreach ( $result as $row ) {
			$title = $row->title;
			$hash = $row->hash;

			// Get the duplicates
			$result2 = $dbr->select( 'image', [ 'title' => 'img_name' ], [ 'img_sha1' => $hash ] );
			foreach ( $result2 as $row2 ) {
				$title = $row2->title;
				$this->output( $title . PHP_EOL );

				// @todo Delete all duplicates with no uses
				// Making redirect won't work because the files are preserved even though the file pages become redirects
			}
			$this->output( PHP_EOL );
		}
	}
}

$maintClass = AddCategoryData::class;
require_once RUN_MAINTENANCE_IF_MAIN;