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

class ArchiveDeadLinks extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Replace dead links for the latest archive.org snapshot' );
		$this->addOption( 'id', 'Only fix dead links for this page ID.', false, true );
		$this->addOption( 'offset', 'How many pages to skip', false, true );
	}

	public function execute() {

		// Set some useful variables
		$services = MediaWikiServices::getInstance();
		$factory = $services->getWikiPageFactory();
		$config = $services->getMainConfig();
		$account = $config->get( 'AppropediaBotAccount' );
		$user = User::newSystemUser( $account );

		// Get the ids to process
		$id = $this->getOption( 'id' );
		if ( $id ) {
			$ids = [ $id ];
		} else {
			$provider = $services->getConnectionProvider();
			$dbr = $provider->getReplicaDatabase();
			$ids = $dbr->newSelectQueryBuilder()
				->fields( 'DISTINCT el_from' )
				->from( 'externallinks' )
				->fetchFieldValues();
		}
		$total = count( $ids );

		$offset = $this->getOption( 'offset', 0 );
		foreach ( $ids as $count => $id ) {
			if ( $count < $offset ) {
				continue;
			}

			// Output how far we've gone
			$this->output( $count + 1 . '/' . $total );

			// Get the title
			$title = Title::newFromID( $id );
			if ( !$title->exists() ) {
				$this->output( ' .. page does not exist' . PHP_EOL );
				continue;
			}
			$page = $factory->newFromTitle( $title );
			$content = $page->getContent();
			if ( $title->isRedirect() ) {
				$title = $content->getRedirectTarget();
				if ( !$title->exists() ) {
					$this->output( ' .. redirect target does not exist' . PHP_EOL );
					continue;
				}
			}
			$text = $title->getFullText();
			$this->output( ' ' . $text . PHP_EOL );

			// Find all the external URLs in the wikitext
			$regex = "(https?\:\/\/)"; // Scheme
			$regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host
			$regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
			$regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // Query
			$regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor
			$wikitext = $content->getText();
			preg_match_all( "/$regex/", $wikitext, $matches );
			$urls = $matches[0];

			foreach ( $urls as $url ) {

				// Check the URL response code
				$curl = curl_init( $url );
				curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt( $curl, CURLOPT_NOBODY, true );
				curl_setopt( $curl, CURLOPT_TIMEOUT_MS, 9999 );
				curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT_MS, 9999 );
				curl_exec( $curl );
				$httpCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
				curl_close( $curl );
				if ( !in_array( $httpCode, [ 0, 403, 404, 410 ] ) ) {
					continue;
				}

				// Output where we're at
				$this->output( $url . ' ' . $httpCode );

				// Don't double-archive dead archive.org links
				if ( preg_match( '@https?://web\.archive\.org/web/\d+/(.+)@', $url, $matches ) ) {
					$url = $matches[1];
				}

				// Get the archived link
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

				// Replace the dead URL
				$wikitext = str_replace( $url, $archived, $wikitext );

				// Save the changes
				$content = ContentHandler::makeContent( $wikitext, $title );
				$updater = $page->newPageUpdater( $user );
				$updater->setContent( 'main', $content );
				$comment = CommentStoreComment::newUnsavedComment( 'Replace dead link for archived version' );
				$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );

				$this->output( ' .. archived!' . PHP_EOL );
			}
		}
	}
}

$maintClass = ArchiveDeadLinks::class;
require_once RUN_MAINTENANCE_IF_MAIN;