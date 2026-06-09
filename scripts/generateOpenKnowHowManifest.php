<?php

/**
 * This script generates a YAML file containing the Open Know How Manifest for a given project.
 */

// Debug mode
$debug = $_GET['debug'] ?? false;
if ( $debug ) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
}

$title = $_GET['title'] ?? $argv[1] ?? exit( 'Error! The "title" param is required.' );
$title = stripcslashes( str_replace( '_', ' ', $title ) ); // Basic decoding
$titlee = str_replace( ' ', '_', $title ); // Extra "e" means "encoded"
$titlee = urlencode( $titlee );

// Connect to the API using EasyWiki (https://github.com/Sophivorus/EasyWiki)
require '/home/appropedia/public_html/w/extensions/Appropedia/vendor/autoload.php';
$api = new EasyWiki( 'https://www.appropedia.org/w/api.php' );

// Get the page properties
$params = [
	'titles' => $title,
	'prop' => 'extracts|pageimages|revisions',
	'explaintext' => 1,
	'exsentences' => 10,
	'pithumbsize' => 1000,
	'rvprop' => 'timestamp',
	'rvlimit' => '500',
];
$result = $api->query( $params );
$missing = $api->find( 'missing', $result );
if ( $missing ) {
	exit( 'Page not found' );
}
//echo '<pre>'; var_dump( $result ); exit; // Uncomment to debug

// Process the page properties
$image = $api->find( 'source', $result );
$revisions = $api->find( 'revisions', $result );
$revisions = is_string( $revisions ) ? [ $revisions ] : $revisions;
$version = count( $revisions );
$dateCreated = end( $revisions )['timestamp'];
$dateCreated = substr( $dateCreated, 0, -10 );
$dateUpdated = reset( $revisions )['timestamp'];
$dateUpdated = substr( $dateUpdated, 0, -10 );
$extract = $api->find( 'extract', $result );
$extract = preg_split( '/\n==+.+==\n/', $extract ); // Split by section
$extract = array_filter( $extract ); // Remove empty sections
$extract = reset( $extract ); // Use the first section

// Get the semantic properties
$contents = @file_get_contents( 'https://www.appropedia.org/w/rest.php/v1/page/' . $titlee . '/semantic' );
$properties = $contents ? json_decode( $contents, true ) : [];
$keywords = $properties['Keywords'] ?? '';
$authors = $properties['Project authors'] ?? $properties['Authors'] ?? '';
$description = $properties['Project description'] ?? $extract ?? '';
$status = $properties['Project status'] ?? '';
$made = $properties['Project was made'] ?? '';
$uses = $properties['Project uses'] ?? '';
$type = $properties['Project type'] ?? '';
$location = $properties['Location'] ?? '';
$license = $properties['License'] ?? 'CC-BY-SA-4.0';
$organizations = $properties['Organizations'] ?? '';
$sdg = $properties['SDG'] ?? '';
$language = $properties['Language code'] ?? 'en';
//echo '<pre>'; var_dump( $properties ); exit; // Uncomment to debug

// Process the semantic properties
$authors = explode( ',', $authors );
$mainAuthor = $authors[0]; // @todo The next version of OKH will support multiple authors
$mainAuthor = preg_replace( "/''+/", '', $mainAuthor ); // Remove italics and bold
if ( $mainAuthor == 'User:Anonymous1 ') {
	$mainAuthor = '';
}
$description = preg_replace( '/\n+/', ' ', $description ); // Merge paragraphs
$description = trim( $description );
$organizations = explode( ',', $organizations );
$affiliation = $organizations[0];
$location = explode( ';', $location );
$location = $location[0];
$keywords = explode( ',', $keywords );
$keywords = array_map( function ( $keyword ) {
	$keyword = trim( $keyword );
	$keyword = ltrim( $keyword, '[' );
	$keyword = rtrim( $keyword , ']' );
	$keyword = strtolower( $keyword );
	return $keyword;
}, $keywords );
$keywords = array_filter( $keywords );

// Build the YAML file
header( 'Content-Type: application/x-yaml' );
header( 'Content-Disposition: attachment; filename=' . $titlee . '.yaml' );
header( 'Access-Control-Allow-Origin: *' );

echo "# Open know-how manifest 1.0
# The content of this manifest file is licensed under a Creative Commons Attribution 4.0 International License. 
# Licenses for modification and distribution of the hardware, documentation, source-code, etc are stated separately.

# Manifest metadata
date-created: $dateCreated
date-updated: $dateUpdated
manifest-author:
  name: Appropedia bot
  affiliation: Appropedia
  email: admin@appropedia.org
documentation-language: $language

# Properties
title: $title" . ( $description ? ( "
description: $description" ) : '' ) . ( $uses ? ( "
intended-use: $uses" ) : '' ) . ( $keywords ? ( '
keywords:
  - ' . implode( "\n  - ", $keywords ) ) : '' ) . "
project-link: https://www.appropedia.org/$titlee
contact:
  name: $mainAuthor
  social:
    platform: Appropedia
    user-handle: $mainAuthor" . ( $location ? "
location: $location" : '' ). ( $image ? "
image: $image" : '' ) . ( $version ? "
version: $version" : '' ) . ( $status ? "
development-stage: $status" : '' ) . ( $made ? "
made: " . ( $made === 't' ? 'true' : 'false' ) : '' ) . ( $type ? "
variant-of:
  name: $type
  web: https://www.appropedia.org/" . str_replace( ' ', '_', $type ) : '' ) . "

# License
license:
  documentation: $license
licensor:
  name: $mainAuthor" . ( $affiliation ? "
  affiliation: $affiliation" : '' ) . "
  contact: https://www.appropedia.org/" . str_replace( ' ', '_', $mainAuthor ) . "
  documentation-home: https://www.appropedia.org/$titlee

# User-defined fields" . ( $sdg ? "
sustainable-development-goals: $sdg" : '' );