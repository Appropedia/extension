<?php

/**
 * This maintenance script replaces dead links for the latest archive.org snapshot
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;
use MediaWiki\ExternalLinks\LinkFilter;
use Miraheze\RottenLinks\RottenLinks;

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
$wgShowSQLErrors = true;
$wgShowExceptionDetails = true;
$wgDebugToolbar = true;
$wgDevelopmentWarnings = true;

class ArchiveDeadLinks extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Replace dead links for the latest archive.org snapshot' );
	}

	public function execute() {

		// Set some useful variables
		$services = MediaWikiServices::getInstance();
		$factory = $services->getWikiPageFactory();
		$config = $services->getMainConfig();
		$account = $config->get( 'AppropediaBotAccount' );
		$user = User::newSystemUser( $account );

		// Get the external links
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
		$results = $dbr->newSelectQueryBuilder()
			->select( [ 'el_from', 'el_to_domain_index', 'el_to_path' ] )
			->from( 'externallinks' )
			->where( 'el_to_domain_index != "https://org.archive.web."' )
			->caller( __METHOD__ )
			->fetchResultSet();
		$total = count( $results );

		foreach ( $results as $count => $result ) {

			// Get the URL
			$toDomainIndex = $result->el_to_domain_index;
			$toPath = $result->el_to_path;
			$url = LinkFilter::reverseIndexes( $toDomainIndex ) . $toPath;

			// Get the response code
			$response = RottenLinks::getResponse( $url );
			$badCodes = $config->get( 'RottenLinksBadCodes' );
			if ( !in_array( $response, $badCodes ) ) {
				continue;
			}

			// Output the working URL
			$percent = round( $count / $total * 100, 2 );
			$this->output( "$percent% $response $url" );

			// Get the latest snapshot
			sleep( 1 ); // Don't overload archive.org
			$curl = curl_init();
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $curl, CURLOPT_URL, 'https://archive.org/wayback/available?url=' . urlencode( $url ) );
			$json = curl_exec( $curl );
			curl_close( $curl );
			if ( !$json ) {
				$this->output( ' .. archive.org returned no JSON' . PHP_EOL );
				continue;
			}
			$json = json_decode( $json, true );
			if ( empty( $json['archived_snapshots'] ) or empty( $json['archived_snapshots']['closest'] ) ) {
				$this->output( ' .. archive.org returned no snapshots' . PHP_EOL );
				continue;
			}
			$archived = $json['archived_snapshots']['closest']['url'];
			if ( !trim( $archived ) ) {
				$this->output( ' .. archive.org returned no closest URL' . PHP_EOL );
				continue;
			}
			
			// Get the wikitext
			$id = $result->el_from;
			$title = Title::newFromID( $id );
			$page = $factory->newFromTitle( $title );
			$content = $page->getContent();
			$text = $title->getFullText();
			$wikitext = $content->getText();

			// Replace the dead URL
			$archived = 'http://web.archive.org/' . $url;
			$wikitext = str_replace( $url, $archived, $wikitext );

			// Save the changes
			$content = ContentHandler::makeContent( $wikitext, $title );
			$updater = $page->newPageUpdater( $user );
			$updater->setContent( 'main', $content );
			$comment = CommentStoreComment::newUnsavedComment( 'Replace dead link for archived version' );
			$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );

			$this->output( ' .. archived!' . PHP_EOL );

			break;
		}
	}
}

$maintClass = ArchiveDeadLinks::class;
require_once RUN_MAINTENANCE_IF_MAIN;