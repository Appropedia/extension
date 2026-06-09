<?php

/**
 * This script generates a ZIM file containing a specified set of pages.
 * See the README for details.
 */

// Debug mode
$debug = $_GET['debug'] ?? false;
if ( $debug ) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
}

// Load EasyWiki
require 'vendor/autoload.php';

// Fetch the pages
$pages = $_GET['pages'] ?? exit( 'Error! The "pages" param is required.' );
$pages = urldecode( $pages );
$pages = str_replace( '_', ' ', $pages );
$pages = str_replace( ',', '|', $pages );
$params = [ 'titles' => $pages ];
$api = new EasyWiki( 'https://www.appropedia.org/w/api.php' );
$result = $api->query( $params );
//echo '<pre>'; var_dump( $result ); exit; // Uncomment to debug

// Replace spaces for underscores or mwoffliner won't work
$titles = $api->find( 'title', $result );
if ( is_string( $titles ) ) {
	$titles = [ $titles ];
}
$titles = array_map( function ( $title ) {
	return str_replace( ' ', '_', $title );
}, $titles );
//echo '<pre>'; var_dump( $titles ); exit; // Uncomment to debug

// Build the mwoffliner command
$title = $_GET['title'] ?? 'appropedia';
$title = substr( $title, 0, 30 ); // Title cannot have more than 30 chars
$titlee = str_replace( ' ', '_', $title ); // Extra "e" is for "encoded"
$icon = $_GET['icon'] ? urldecode( $_GET['icon'] ) : 'https://www.appropedia.org/logos/Appropedia-kiwix.png';
$description = $_GET['description'] ?? 'From Appropedia, the sustainability wiki';
$description = substr( $description, 0, 80 ); // Description cannot have more than 80 chars
$mainpage = $_GET['mainpage'] ?? '';
$mainpagee = str_replace( ' ', '_', $mainpage );
$command = 'mwoffliner';
$command .= ' --adminEmail=admin@appropedia.org';
$command .= ' --customZimTitle="' . $title . '"';
$command .= ' --customZimFavicon=' . $icon;
$command .= ' --customZimDescription="' . $description . '"';
$command .= ' --customMainPage=' . $mainpagee;
$command .= ' --filenamePrefix=' . $titlee;
$command .= ' --mwUrl=https://www.appropedia.org/';
$command .= ' --mwWikiPath=/';
$command .= ' --outputDirectory=/home/appropedia/public_html/scripts';
$command .= ' --osTmpDir=/home/appropedia/public_html/scripts';
$command .= ' --publisher=Appropedia';
$command .= ' --requestTimeout=120';
$command .= ' --webp';
$command .= ' --articleList="' . implode( ',', $titles ) . '"';
$command .= ' --verbose';
//echo '<pre>' . $command; exit; // Uncomment to debug

// Make the ZIM file (this may take several seconds)
exec( $command, $output );
//echo '<pre>' . var_dump( $output ); exit; // Uncomment to debug

// Download the ZIM file
$date = date( 'Y-m' );
$filename = $titlee . '_' . $date . '.zim';
header( 'Content-Type: application/octet-stream' );
header( 'Content-Disposition: attachment; filename=' . $title . '.zim' );
header( 'Access-Control-Allow-Origin: *' );
readfile( $filename );
unlink( $filename );
