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

class GenerateOpenKnowHowManifests extends Maintenance {

	public function execute() {

		// Make sure the manifests dir exists and is empty
		$dir = '/home/appropedia/public_html/manifests';
		if ( is_dir( $dir ) ) {
			exec( "rm -f $dir/*" );
		} else {
			mkdir( $dir );
		}

		// Make a manifest for each project
		$manifests = [];
		$category = Category::newFromName( 'Projects' );
		$projects = $category->getMembers();
		$total = $category->getPageCount( Category::COUNT_CONTENT_PAGES );
		foreach ( $projects as $count => $project ) {
			if ( !$project->isContentPage() ) {
				continue;
			}
			$title = $project->getFullText();
			if ( preg_match( '#/[a-z]{2}$#', $title ) ) {
				continue; // Skip automatic translations, for now
			}
			$titlee = str_replace( ' ', '_', $title ); // Extra "e" means "encoded"
			$url = "https://www.appropedia.org/scripts/generateOpenKnowHowManifest.php?title=$titlee";
			$manifest = file_get_contents( $url );
			$hash = md5( $title );
			$manifests[] = "https://www.appropedia.org/manifests/$hash.yaml";
			file_put_contents( "$dir/$hash.yaml", $manifest );
			$this->output( "$count/$total $title" . PHP_EOL );
			//break; // Uncomment to debug
		}

		// Make the list.json index
		$json = json_encode( $manifests, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		file_put_contents( $dir . '/list.json', $json );
		$this->output( 'list.json' . PHP_EOL );
	}
}

$maintClass = GenerateOpenKnowHowManifests::class;
require_once RUN_MAINTENANCE_IF_MAIN;