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

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Delete duplicate files leaving redirects behind' );
		$this->addOption( 'delete', 'Actually do the deletions' );
	}

	public function execute() {

		// Get the local repo
		$services = MediaWikiServices::getInstance();
		$group = $services->getRepoGroup();
		$repo = $group->getLocalRepo();

		// Get files with duplicates
		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
		$results = $dbr->newSelectQueryBuilder()
			->fields( [ 'hash' => 'img_sha1', 'count' => 'count(img_sha1)' ] )
			->from( 'image' )
			->groupBy( 'img_sha1' )
			->having( 'count > 1' )
			->fetchResultSet();

		foreach ( $results as $result ) {
			$hash = $result->hash;
			$count = $result->count;

			// Get all versions
			$duplicates = $dbr->newSelectQueryBuilder()
				->field( 'img_name', 'name' )
				->from( 'image' )
				->where( [ 'img_sha1' => $hash ] )
				->orderBy( 'img_timestamp', 'asc' )
				->limit( $count )
				->fetchResultSet();

			// Select the version with most info as canonical
			// @todo Prioritize files with uses
			$canonical = null;
			foreach ( $duplicates as $duplicate ) {
				$title = Title::newFromText( $duplicate->name, NS_FILE );
				if ( !$canonical || $title->getLength() > $canonical->getLength() ) {
					$canonical = $title;
				}
			}
			$this->output( $canonical->getText() . PHP_EOL );

			// Delete the rest and leave redirects behind
			foreach ( $duplicates as $duplicate ) {

				// Skip the canonical
				if ( $duplicate->name === $canonical->getDBkey() ) {
					continue;
				}

				// Output the working title
				$this->output( "\t" . $duplicate->name );

				// Check if actually do the deletions
				$delete = $this->getOption( 'delete' );
				if ( !$delete ) {
					$this->output( PHP_EOL );
					continue;
				}

				// Delete the file
				$file = $repo->newFile( $duplicate->name );
				$reason = 'Duplicate file';
				$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER );
				$status = $file->deleteFile( $reason, $user );
				if ( !$status->isOK() ) {
					$this->output( ' .. could not delete' . PHP_EOL );
					continue;
				}

				// Create redirect
				$title = Title::newFromText( $duplicate->name, NS_FILE );
				$factory = $services->getWikiPageFactory();
				$wikiPage = $factory->newFromTitle( $title );
				$updater = $wikiPage->newPageUpdater( $user );
				$wikitext = '#REDIRECT [[' . $canonical->getFullText() . ']]';
				$content = ContentHandler::makeContent( $wikitext, $title );
				$updater->setContent( 'main', $content );
				$comment = CommentStoreComment::newUnsavedComment( $reason );
				$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );

				// Output the result
				$status = $updater->getStatus();
				if ( $status->isOK() ) {
					$this->output( ' .. deleted' . PHP_EOL );
				} else {
					$this->output( ' .. file deleted but redirect could not be created' . PHP_EOL );
				}
			}
			//break; // Uncomment to debug
		}
	}
}

$maintClass = DeleteDuplicateFiles::class;
require_once RUN_MAINTENANCE_IF_MAIN;