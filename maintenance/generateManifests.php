<?php

/**
 * This script generates an Open Know How Manifest for each project in Appropedia
 * and adds it to the manifests directory
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;
use Sophivorus\EasyWiki;

class GenerateManifests extends Maintenance {

	public function execute() {

		// Delete manifests
		exec( 'rm -f /home/appropedia/public_html/manifests/*' );
		
		// Get all projects
		$api = new EasyWiki( 'https://www.appropedia.org/w/api.php' );
		$params = [ 'action' => 'askargs', 'conditions' => 'Type::Project' ];
		$result = $api->get( $params );
		//var_dump( $result ); exit; // Uncomment to debug
		$results = $api->find( 'results', $result );
		
		// Make manifest for each project
		foreach ( $results as $title => $values ) {
			echo $title . PHP_EOL;
			$hash = md5( $title );
			$titlee = str_replace( ' ', '_', $title ); // Basic encoding
			$url = "https://www.appropedia.org/scripts/generateOpenKnowHowManifest.php?title=$titlee";
			$manifest = file_get_contents( $url );
			$manifest = trim( $manifest );
			file_put_contents( "../manifests/$hash.yaml", $manifest );
			//exit; // Uncomment to debug
		}
		
		// Make list.json index
		$dir = '/home/appropedia/public_html/manifests/';
		if ( is_dir( $dir ) ) {
			$list = [];
			if ( $dir_handle = opendir( $dir ) ) {
				while ( ( $file = readdir( $dir_handle ) ) !== false ) {
					if ( substr( $file, 0, 1 ) === '.' ) {
						continue; // Skip hidden files
					}
					$url = 'https://www.appropedia.org/manifests/' . $file;
					$list[] = $url;
				}
			}
			echo 'list.json' . PHP_EOL;
			$json = json_encode( $list, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
			file_put_contents( $dir . 'list.json', $json );
		}
	}
}

$maintClass = GenerateManifests::class;
require_once RUN_MAINTENANCE_IF_MAIN;