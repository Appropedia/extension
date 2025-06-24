<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use PageImages\PageImages;
use chillerlan\QRCode\QRCode;

/**
 * Class to get all projects or a single one in various formats
 * GET /projects
 * GET /projects/{project}
 * GET /projects/{project}/{format}
 */
class AppropediaProjects extends SimpleHandler {

	public function run( $project = null, $format = null ) {

		if ( $format ) {
			self::$format( $project );
			exit;
		}

		if ( $project ) {
			// @todo Only output project-specific data
			$data = self::getSemanticData( $project );
			$data['logo'] = self::getLogo( $project );
			return $data;
		}

		$projects = [];

		$request = $this->getRequest();
		$params = $request->getQueryParams();
		$language = $params['language'] ?? null;
		$translations = $params['translations'] ?? null;

		// We may need the following variables in the loop
		$services = MediaWikiServices::getInstance();
		$languageUtils = $services->getLanguageNameUtils();

		$category = Category::newFromName( 'Projects' );
		$members = $category->getMembers();
		foreach ( $members as $member ) {

			// @todo Find a better way to check for booleans
			if ( in_array( $translations, [ 'false', 'f', 'no', 'n', 'off', '0' ] ) ) {
				$subpage = $member->getSubpageText();
				if ( $languageUtils->isSupportedLanguage( $subpage ) ) {
					continue;
				}
			}

			if ( $language ) {
				$languageObject = $member->getPageLanguage();
				$languageCode = $languageObject->getCode();
				if ( $languageCode !== $language ) {
					continue;
				}
			}

			$projects[] = $member->getFullText();
		}
		return $projects;
	}

	public static function pdf( $project ) {
		$command = 'wkhtmltopdf';
		$command .= ' --user-style-sheet ' . __DIR__ . '/resources/AppropediaProjectsPDF.css';
		$command .= ' --footer-center [page]';

		// Make the cover and add it
		$cover = '<!DOCTYPE HTML>';
		$cover .= '<html>';
		$cover .= '<head>';
		$cover .= '<meta charset="utf-8">';
		$cover .= '<title>' . $project . '</title>';
		$cover .= '</head>';
		$cover .= '<body id="cover">';
		$cover .= '<header>';
		$logo = self::getLogo( $project );
		if ( $logo ) {
			$cover .= '<img id="cover-logo" src="' . $logo . '" />';
		}
		$cover .= '<h1 id="cover-title">' . $project . '</h1>';
		$subtitle = self::getSubtitle( $project );
		if ( $subtitle ) {
			$cover .= '<p id="cover-subtitle">' . $subtitle . '</p>';
		}
		$cover .= '</header>';
		$title = Title::newFromText( $project );
		$url = $title->getFullURL();
		$qrcode = new QRCode;
		$src = $qrcode->render( $url );
		$cover .= '<img id="cover-qrcode" src="' . $src . '" />';
		$cover .= '<footer>';
		$cover .= '<img id="cover-appropedia" src="https://www.appropedia.org/logos/Appropedia-logo.png" />';
		$cover .= '</footer>';
		$cover .= '</body>';
		$cover .= '</html>';
		file_put_contents( 'cover.html', $cover );
		$command .= ' cover cover.html';

		// Set the pages
		$pages = self::getPages( $project );
		foreach ( $pages as $page ) {
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
		header( 'Content-Disposition: attachment; filename=' . $project . '.pdf' );
		readfile( 'temp.pdf' );

		// Clean up
		unlink( 'temp.pdf' );
		unlink( 'cover.html' );
	}

	public static function zim( $project ) {
		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		$dir = $config->get( 'UploadDirectory' );

		$favicon = 'https://www.appropedia.org/logos/Appropedia-kiwix.png';
		$logo = self::getLogo( $project );
		if ( $logo ) {
			$favicon = 'https://www.appropedia.org' . $logo;
		}

		$title = str_replace( '_', ' ', $project );
		$title = substr( $title, 0, 30 ); // ZIM titles cannot have more than 30 chars
		$titlee = str_replace( ' ', '_', $title ); // Extra "e" means "encoded"
	
		$description = self::getSubtitle( $project );
		$description = substr( $description, 0, 80 ); // ZIM descriptions cannot have more than 80 chars

		$pages = self::getPages( $project );
		$pages = implode( ',', $pages );
		$pages = str_replace( ' ', '_', $pages ); // mwoffliner requires underscores

		// Build the mwoffliner command
		$command = 'mwoffliner';
		$command .= ' --adminEmail=admin@appropedia.org';
		$command .= ' --customZimTitle="' . $title . '"';
		$command .= ' --customZimFavicon=' . $favicon;
		$command .= ' --customZimDescription="' . $subtitle . '"';
		$command .= ' --filenamePrefix=' . $titlee;
		$command .= ' --mwUrl=https://www.appropedia.org';
		$command .= ' --mwWikiPath=/';
		$command .= ' --osTmpDir=' . $dir;
		$command .= ' --outputDirectory=' . $dir;
		$command .= ' --publisher=Appropedia';
		$command .= ' --webp';
		$command .= ' --articleList="' . $pages . '"';
		//$command .= ' --verbose';
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
	}

	public static function okh( $title ) {
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
		$properties = self::getSemanticData( $title );
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
	}

	public static function getPages( $project ) {
		$pages = [];
		$category = Category::newFromName( $project );
		$members = $category->getMembers();
		foreach ( $members as $member ) {
			if ( $member->isContentPage() ) {
				$pages[] = $member->getFullText();
			}
		}
		return $pages;
	}

	public static function getSemanticData( $project ) {
		$semanticRestApi = new SemanticRESTAPI;
		$data = $semanticRestApi->run( $project );
		return $data;
	}
	
	public static function getLogo( $project ) {
		$title = Title::newFromText( $project );
		$image = PageImages::getPageImage( $title );
		if ( $image ) {
			return $image->createThumb( 100 );
		}
	}

	public static function getSubtitle( $project ) {
		return ''; // @todo
	}
	
	/** @inheritDoc */
	public function needsWriteAccess() {
		return false;
	}

	/** @inheritDoc */
	public function getParamSettings() {
		return [
			'title' => [
				self::PARAM_SOURCE => 'path'
			],
			'format' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => [ 'pdf', 'zim', 'okh' ]
			],
			'language' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			],
			'translations' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'boolean'
			]
		];
	}
}