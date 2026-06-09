<?php

/**
 * This script generates a PDF containing a specified set of pages.
 * @todo Switch from wkhtmltopdf to dompdf or mPDF or headless Chrome
 */

// Debug mode
$debug = $_GET['debug'] ?? false;
if ( $debug ) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
}

// Load the library for generating QR codes
require '../vendor/autoload.php';
use chillerlan\QRCode\QRCode;

// Get the params
$pages = $_GET['pages'] ?? exit( 'Error! The "pages" param is required.' );
$title = $_GET['title'] ?? null;
$subtitle = $_GET['subtitle'] ?? null;
$logo = $_GET['logo'] ?? null;
$logowidth = $_GET['logowidth'] ?? 100;
$qrpage = $_GET['qrpage'] ?? null;

// Start building the command
$command = 'wkhtmltopdf';
$command .= ' --print-media-type';
$command .= ' --user-style-sheet generatePDF.css';
$command .= ' --footer-center [page]';

// Set the cover
if ( $title ) {
	$html = '<!DOCTYPE HTML>';
	$html .= '<html>';
	$html .= '<head>';
	$html .= '<meta charset="utf-8">';
	$html .= '<title>' . $title . '</title>';
	$html .= '</head>';
	$html .= '<body id="cover">';
	$html .= '<header>';
	if ( $logo ) {
		$html .= '<img id="cover-logo" src="' . $logo . '" width="' . $logowidth . '" />';
	}
	$html .= '<h1 id="cover-title">' . $title . '</h1>';
	if ( $subtitle ) {
		$html .= '<p id="cover-subtitle">' . $subtitle . '</p>';
	}
	$html .= '</header>';
	if ( $qrpage ) {
		$qrpage = str_replace( ' ', '_', $qrpage );
		$qrcode = new QRCode;
		$src = $qrcode->render( 'https://www.appropedia.org/' . $qrpage );
		$html .= '<img id="cover-qrcode" src="' . $src . '" />';
	}
	$html .= '<footer>';
	$html .= '<img id="cover-appropedia" src="https://www.appropedia.org/logos/Appropedia-logo.png" />';
	$html .= '</footer>';
	$html .= '</body>';
	$html .= '</html>';
	file_put_contents( 'cover.html', $html );
	$command .= ' cover cover.html';
}

// Set the pages
$pages = explode( ',', $pages );
foreach ( $pages as $page ) {
	$page = urldecode( $page );
	$page = trim( $page );
	$page = str_replace( ' ', '_', $page );
	$page = urlencode( $page );
	$url = "https://www.appropedia.org/$page";
    $command .= " $url";
}

// Set the output
$command .= ' temp.pdf';
exec( $command );

// Download the PDF
$filename = $title ? $title : 'appropedia';
header( 'Content-Type: application/pdf' );
header( 'Content-Disposition: attachment; filename=' . $filename . '.pdf' );
header( 'Access-Control-Allow-Origin: *' );
readfile( 'temp.pdf' );

// Clean up
unlink( 'temp.pdf' );
unlink( 'cover.html' );