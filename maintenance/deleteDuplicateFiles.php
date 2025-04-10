<?php

/**
 * This script deletes all duplicate files and leaves redirects behind
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class DeleteDuplicateFiles extends Maintenance {

	public function execute() {

		// Get files with duplicates
		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
		$results = $dbr->newSelectQueryBuilder()
			->fields( [
				'name' => 'img_name',
				'hash' => 'img_sha1',
				'count' => 'count(img_sha1)'
			] )
			->from( 'image' )
			->groupBy( 'img_sha1' )
			->having( 'count > 1' )
			->fetchResultSet();

		foreach ( $results as $row ) {
			$name = $row->name;
			$hash = $row->hash;
			$duplicates = $row->count - 1;

			$this->output( $name . " .. $duplicates duplicates" . PHP_EOL );

			// Get the duplicates
			$results2 = $dbr->newSelectQueryBuilder()
				->field( 'img_name' )
				->from( 'image' )
				->where( [ 'img_sha1' => $hash ] )
				->fetchResultSet();

			foreach ( $results2 as $row2 ) {
				$name2 = $row2->img_name;
				//$this->output( $name2 . PHP_EOL );

				// @todo Delete all duplicates and leave redirects behind
			}
		}
	}
}

$maintClass = DeleteDuplicateFiles::class;
require_once RUN_MAINTENANCE_IF_MAIN;