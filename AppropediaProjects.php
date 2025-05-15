<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use chillerlan\QRCode\QRCode;

/**
 * Class to get all projects or a single one in various formats
 * GET /projects/{title}/{format}
 */
class AppropediaProjects extends SimpleHandler {

	public function run( $title, $format ) {
		$request = $this->getRequest();
		$params = $request->getQueryParams();
		if ( $format ) {
			self::$format( $title, $params );
		}
	}

	public static function pdf( $title, $params ) {
		$pages = $params['pages']; // Required
		$logo = $params['logo'] ?? false;
		$subtitle = $params['subtitle'] ?? false;
		$text = $params['text'] ?? false;

		// Start building the command
		$command = 'wkhtmltopdf';
		$command .= ' --user-style-sheet ' . __DIR__ . '/resources/AppropediaProjectsPDF.css';
		$command .= ' --footer-center [page]';

		// Make the cover and add it
		$cover = '<!DOCTYPE HTML>';
		$cover .= '<html>';
		$cover .= '<head>';
		$cover .= '<meta charset="utf-8">';
		$cover .= '<title>' . $title . '</title>';
		$cover .= '</head>';
		$cover .= '<body id="cover">';
		$cover .= '<header>';
		if ( $logo ) {
			$services = MediaWikiServices::getInstance();
			$repo = $services->getRepoGroup();
			$file = $repo->findFile( $logo );
			$src = $file->createThumb( 100 );
			$cover .= '<img id="cover-logo" src="' . $src . '" />';
		}
		$cover .= '<h1 id="cover-title">' . $title . '</h1>';
		if ( $subtitle ) {
			$cover .= '<p id="cover-subtitle">' . $subtitle . '</p>';
		}
		$cover .= '</header>';
		if ( $text ) {
			$cover .= '<p id="cover-text">' . $text . '</p>';
		}
		$titleObject = Title::newFromText( $title );
		$titleUrl = $titleObject->getFullURL();
		$qrcode = new QRCode;
		$src = $qrcode->render( $titleUrl );
		$cover .= '<img id="cover-qrcode" src="' . $src . '" />';
		$cover .= '<footer>';
		$cover .= '<img id="cover-appropedia" src="https://www.appropedia.org/logos/Appropedia-logo.png" />';
		$cover .= '</footer>';
		$cover .= '</body>';
		$cover .= '</html>';
		file_put_contents( 'cover.html', $cover );
		$command .= ' cover cover.html';

		// Set the pages
		$pages = explode( '|', $pages );
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

		// Make the PDF
		exec( $command );

		// Download the PDF
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename=' . $title . '.pdf' );
		readfile( 'temp.pdf' );

		// Clean up
		unlink( 'temp.pdf' );
		unlink( 'cover.html' );

		// Exit because the method is expected to return JSON
		exit;
	}

	public static function zim( $title, $params ) {

		// Get the params
		$pages = $params['pages']; // Required
		$logo = $params['logo'] ?? false;
		$subtitle = $params['subtitle'] ?? false;

		// Process the params and set some other variables
		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		$dir = $config->get( 'UploadDirectory' );

		$title = str_replace( '_', ' ', $title );
		$title = substr( $title, 0, 30 ); // ZIM titles cannot have more than 30 chars
		$titlee = str_replace( ' ', '_', $title ); // Extra "e" means "encoded"
	
		$description = '';
		if ( $subtitle ) {
			$description = substr( $subtitle, 0, 80 ); // ZIM descriptions cannot have more than 80 chars
		}

		$pages = str_replace( '|', ',', $pages ); // mwoffliner requires a comma-separated list
		$pages = str_replace( ' ', '_', $pages ); // mwoffliner requires underscores

		$favicon = 'https://www.appropedia.org/logos/Appropedia-kiwix.png';
		if ( $logo ) {
			$repo = $services->getRepoGroup();
			$file = $repo->findFile( $logo );
			$favicon = $file->createThumb( 100 );
		}

		// Build the mwoffliner command
		$command = 'mwoffliner';
		$command .= ' --adminEmail=admin@appropedia.org';
		$command .= ' --customZimTitle="' . $title . '"';
		$command .= ' --customZimFavicon=' . $favicon;
		$command .= ' --customZimDescription="' . $description . '"';
		$command .= ' --filenamePrefix=' . $titlee;
		$command .= ' --mwUrl=https://www.appropedia.org';
		$command .= ' --mwWikiPath=/';
		$command .= ' --osTmpDir=' . $dir;
		$command .= ' --outputDirectory=' . $dir;
		$command .= ' --publisher=Appropedia';
		$command .= ' --webp';
		$command .= ' --articleList="' . $pages . '"';
		$command .= ' --verbose';
		//echo '<pre>' . $command; exit; // Uncomment to debug

		// Make the ZIM file (this may take several seconds)
		exec( $command, $output );
		//echo '<pre>' . var_dump( $output ); exit; // Uncomment to debug

		// Download the ZIM file
		$date = date( 'Y-m' );
		$filename = $dir . '/' . $titlee . '_' . $date . '.zim';
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . $title . '.zim' );
		readfile( $filename );

		// Clean up
		unlink( $filename );

		// Exit because the method is expected to return JSON
		exit;
	}

	public static function okh( $title, $params ) {
		$fauxRequest = new FauxRequest( [
			'titles' => $title,
			'action' => 'query',
			'prop' => 'extracts|pageimages|revisions',
			'exintro' => true,
			'exsentences' => 5,
			'exlimit' => 1,
			'explaintext' => 1,
			'pithumbsize' => 1000,
			'rvprop' => 'timestamp',
			'rvlimit' => 500,
		] );
		$api = new ApiMain( $fauxRequest );
		$api->execute();
		$result = $api->getResult();
		$data = $result->getResultData( [ 'query', 'pages' ], [ 'Strip' => 'all' ] );
		//echo '<pre>'; var_dump( $data ); exit; // Uncomment to debug

		// Extract and process the data
		$page = array_shift( $data );
		$extract = $page['extract']['*'];
		$extract = str_replace( "\n", ' ', $extract );
		$extract = trim( $extract );
		$image = $page['thumbnail']['source'];
		$revisions = $page['revisions'];
		$version = count( $revisions );
		$dateCreated = end( $revisions )['timestamp'];
		$dateCreated = substr( $dateCreated, 0, -10 );
		$dateUpdated = reset( $revisions )['timestamp'];
		$dateUpdated = substr( $dateUpdated, 0, -10 );
		//echo '<pre>'; var_dump( $extract, $image, $version, $dateCreated, $dateUpdated ); exit; // Uncomment to debug

		// Get the semantic properties
		$semanticRestApi = new SemanticRESTAPI;
		$properties = $semanticRestApi->run( $title );
		$keywords = $properties['Keywords'] ?? '';
		$authors = $properties['Project authors'] ?? $properties['Authors'] ?? '';
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

		// Process the properties
		$titlee = str_replace( ' ', '_', $title );
		$authors = explode( ',', $authors );
		$mainAuthor = $authors[0]; // @todo The next version of OKH will support multiple authors
		if ( $mainAuthor == 'User:Anonymous1 ') {
			$mainAuthor = '';
		}
		$organizations = explode( ',', $organizations );
		$affiliation = $organizations[0];
		$location = explode( ',', $location );
		$location = $location[0];

		// Build the YAML file
		header( "Content-Type: application/x-yaml" );
		header( "Content-Disposition: attachment; filename=$title.yaml" );

		echo "# Open know-how manifest 1.0
# The content of this manifest file is licensed under a Creative Commons Attribution 4.0 International License. 
# Licenses for modification and distribution of the hardware, documentation, source-code, etc are stated separately.

# Manifest metadata
date-created: $dateCreated
date-updated: $dateUpdated
manifest-author:
name: OKH Bot
affiliation: Appropedia
email: admin@appropedia.org
documentation-language: $language

# Properties
title: $title" . ( $extract ? ( "
description: $extract" ) : '' ) . ( $uses ? ( "
intended-use: $uses" ) : '' ) . ( $keywords ? ( '
keywords:
- ' . str_replace( ',', "\n  -", $keywords ) ) : '' ) . "
project-link: https://www.appropedia.org/$titlee
contact:
name: $mainAuthor
social:
- platform: Appropedia
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
		exit;
	}

	/** @inheritDoc */
	public function needsWriteAccess() {
		return false;
	}

	/** @inheritDoc */
	public function getParamSettings() {
		return [
			'title' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true
			],
			'format' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => [ 'pdf', 'zim', 'okh' ],
				ParamValidator::PARAM_REQUIRED => true
			],
			'pages' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_REQUIRED => true
			],
			'logo' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			],
			'subtitle' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			],
			'text' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			]
		];
	}
}