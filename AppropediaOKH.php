<?php

use MediaWiki\Request\FauxRequest;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * This endpoint returns a YAML file containing the Open Know How Manifest for a given project
 * @todo Upgrade to OKH 2.0
 */
class AppropediaOKH extends SimpleHandler {

	public function run() {

		$params = $this->getValidatedParams();
		$title = $params['title'];

		// @todo Validate that the page is a project

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
		//return $data; // Uncomment to debug

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
		//return [ $extract, $image, $version, $dateCreated, $dateUpdated ]; // Uncomment to debug

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
		//return $properties; // Uncomment to debug

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

		// @todo Avoid this ugly long string
		$yaml = "# Open know-how manifest 1.0
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

		// Return the YAML file
		$response = $this->getResponseFactory()->create();
		$response->setHeader( 'Content-Type', 'text/yaml; charset=utf-8' );
		$response->setHeader( 'Content-Disposition', 'attachment; filename=' . $title . '.yaml' );
		$response->getBody()->write( $yaml );
        return $response;
	}

	/** @inheritDoc */
	public function needsWriteAccess() {
		return false;
	}

	/** @inheritDoc */
	public function getParamSettings() {
		return [
			'title' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}