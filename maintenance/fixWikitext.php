<?php

/**
 * This maintenance script runs the wikitext fixes defined at AppropediaWikitext for all pages
 * For example, it adds {{Page data}} to all content pages, {{User data}} to all user pages, etc.
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class FixWikitext extends Maintenance {

	public function execute() {

		// Get the pages to fix
		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
		$ids = $dbr->newSelectQueryBuilder()
			->field( 'page_id' )
			->from( 'page' )
			->where( [
				'page_is_redirect' => 0,
				'page_content_model' => CONTENT_MODEL_WIKITEXT,
				'page_namespace' => [ NS_MAIN, NS_USER, NS_FILE, NS_CATEGORY ]
			] )
			->fetchFieldValues();
		$factory = $services->getWikiPageFactory();

		foreach ( $ids as $id ) {

			// Get the working title
			$title = Title::newFromID( $id );
			$text = $title->getFullText();

			$page = $factory->newFromTitle( $title );
			$content = $page->getContent();
			$wikitext = $content->getText();

			// Check if fixing the wikitext changes anything
			$fixed = AppropediaWikitext::fixWikitext( $wikitext, $title );
			if ( $fixed === $wikitext ) {
				continue;
			}

			// Save the fixed wikitext
			AppropediaWikitext::saveWikitext( $fixed, $page );

			// Output the edited page
			$url = $title->getFullURL();
			$this->output( $url . PHP_EOL );
			break; // Uncomment to debug
		}
	}
}

$maintClass = FixWikitext::class;
require_once RUN_MAINTENANCE_IF_MAIN;
