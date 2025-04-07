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

class GenerateManifests extends Maintenance {

	public function execute() {

		// Make sure the manifests dir exists and is empty
		$manifests = '/home/appropedia/public_html/manifests';
		if ( is_dir( $manifests ) ) {
			exec( "rm -f $manifests/*" );
		} else {
			mkdir( $manifests );
		}

		// Get all the projects
		$titles = [];
		$category = Category::newFromName( 'Projects' );
		$members = $category->getMembers();
		foreach ( $members as $title ) {
			$title = $title->getFullText();
			if ( preg_match( '#/[a-z]{2}$#', $title ) ) {
				continue; // Skip automatic translations, for now
			}
			$titles[] = $title;
		}
		//var_dump( $titles ); exit; // Uncomment to debug

		// Make a manifest for each project
		$files = [];
		foreach ( $titles as $title ) {
			echo $title . PHP_EOL;
			$titlee = str_replace( ' ', '_', $title ); // Basic encoding
			$url = "https://www.appropedia.org/scripts/generateOpenKnowHowManifest.php?title=$titlee";
			$manifest = file_get_contents( $url );
			$hash = md5( $title );
			$files[] = "https://www.appropedia.org/manifests/$hash.yaml";
			file_put_contents( "$manifests/$hash.yaml", $manifest );
			//break; // Uncomment to debug
		}

		// Make the list.json index
		$json = json_encode( $files, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		file_put_contents( $manifests . '/list.json', $json );
	}
}

$maintClass = GenerateManifests::class;
require_once RUN_MAINTENANCE_IF_MAIN;