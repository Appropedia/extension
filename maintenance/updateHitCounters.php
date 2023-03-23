<?php

/**
 * This script gets the pageviews for each page from Google Analytics (UA)
 * and updates the hit_counters table of the Extension:HitCounters
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class UpdateHitCounters extends Maintenance {

	public function execute() {

		$queue = file( __DIR__ . '/updateHitCountersQueue' );
		if ( $queue ) {

			// Get the first title from the queue
			$title = trim( array_shift( $queue ) );
			$title or exit( 'Queue is empty' . PHP_EOL );

			// Update the queue
			file_put_contents( __DIR__ . '/updateHitCountersQueue', $queue );

			// Connect to Google Analytics
			$client = new Google_Client();
			$client->setApplicationName( 'Appropedia Analytics' );
			$client->setAuthConfig( '/home/appropedia/google-cloud-credentials.json' );
			$client->setScopes( [ 'https://www.googleapis.com/auth/analytics.readonly' ] );
			$analytics = new Google_Service_Analytics( $client );

			// Connect to the first account
			$accounts = $analytics->management_accounts->listManagementAccounts();
			$items = $accounts->getItems();
			$items or exit( 'No accounts found for this user' . PHP_EOL );
			$account = $items[0]->getId();

			// Connect to the first property
			$properties = $analytics->management_webproperties->listManagementWebproperties( $account );
			$items = $properties->getItems();
			$items or exit( 'No properties found for this user' . PHP_EOL );
			$property = $items[0]->getId();

			// Connect to the first view (profile)
			$profiles = $analytics->management_profiles->listManagementProfiles( $account, $property );
			$items = $profiles->getItems();
			$items or exit( 'No views (profiles) found for this user' . PHP_EOL );
			$profile = $items[0]->getId();

			// Build the filters
			$escapedTitle = str_replace( ',', '\,', str_replace( ';', '\;', $title ) ); // Escape reserved characters
			$filters = "ga:pagePath==/$escapedTitle";
			$Title = Title::newFromText( $title );
			$Title or exit( 'Error making the title' . PHP_EOL );
			$Redirects = $Title->getRedirectsHere();
			foreach ( $Redirects as $Redirect ) {
				// Don't abuse Google Analytics
				// According to https://developers.google.com/analytics/devguides/reporting/core/v4/limits-quotas#reporting_apis
				// we can do 10,000 requests per day, or one every 10 seconds
				sleep( 10 );
				$redirect = $Redirect->getPrefixedDBKey();
				$escapedRedirect = str_replace( ',', '\,', str_replace( ';', '\;', $redirect ) ); // Escape reserved characters
				$filters .= ",ga:pagePath==/$escapedRedirect";
			}

			// Build the query
			// First day with data: 2016-08-23
			// See https://developers.google.com/analytics/devguides/reporting/core/v3/coreDevguide
			$results = $analytics->data_ga->get( "ga:$profile", '2016-08-23', 'today', 'ga:pageviews', [ 'filters' => $filters ] );
			$rows = $results->getRows();
			$rows or exit( 'No page views' . PHP_EOL );

			// Aggregate the pageviews
			$pageviews = 0;
			foreach ( $rows as $row ) {
				$pageviews += array_shift( $row );
			}

			// Store the pageviews
			$id = $Title->getArticleID();
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
			$dbw = $lb->getConnectionRef( DB_PRIMARY );
			$dbw->upsert(
				'hit_counter',
				[
					'page_id' => $id,
					'page_counter' => $pageviews,
				],
				[
					'page_id',
				],
				[
					'page_counter' => $pageviews,
				]
			);

			// Output a log
			$this->output( $id . ' ' . $Title->getFullURL() . ' -> ' . $pageviews . PHP_EOL );

		} else {

			// Queue all non-redirect mainspace pages
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
			$dbr = $lb->getConnectionRef( DB_REPLICA );
			$result = $dbr->select( 'page', 'page_title', [ 'page_namespace' => 0, 'page_is_redirect' => 0 ], __METHOD__, [ 'ORDER BY' => 'page_id DESC' ] );
			$file = fopen( __DIR__ . '/updateHitCountersQueue', 'a' );
			foreach ( $result as $row ) {
				$title = $row->page_title;
				fwrite( $file, $title . PHP_EOL );
			};
			fclose( $file );
		}
	}
}

$maintClass = UpdateHitCounters::class;
require_once RUN_MAINTENANCE_IF_MAIN;