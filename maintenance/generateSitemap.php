<?php

/**
 * This script regenerates the sitemap and pings Google
 * This script is run once a day by a cronjob
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class GenerateSitemap extends Maintenance {

	public function execute() {
		exec( '/usr/local/bin/php /home/appropedia/public_html/w/maintenance/generateSitemap.php --identifier appropedia --fspath /home/appropedia/public_html/sitemap/ --urlpath=/sitemap --server=https://www.appropedia.org --compress=no' );
		file_get_contents( 'https://www.google.com/webmasters/sitemaps/ping?sitemap=https://www.appropedia.org/sitemap/sitemap-index-appropedia.xml' );
	}
}

$maintClass = GenerateSitemap::class;
require_once RUN_MAINTENANCE_IF_MAIN;