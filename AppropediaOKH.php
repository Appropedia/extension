<?php

use MediaWiki\Request\FauxRequest;
use MediaWiki\Rest\SimpleHandler;
use Symfony\Component\Yaml\Yaml;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * This endpoint returns a YAML file containing the Open Know How Manifest for a given project
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
		$keywords = $properties['Page keywords'] ?? [];
		$author = $properties['Project authors'][0] ?? $properties['Page authors'][0] ?? '';
		$status = $properties['Project status'][0] ?? '';
		$made = $properties['Project was made'][0] ?? '';
		$use = $properties['Project uses'][0] ?? '';
		$type = $properties['Project type'][0] ?? '';
		$location = $properties['Page location'][0] ?? '';
		$license = $properties['Page license'][0] ?? 'CC-BY-SA-4.0';
		$affiliation = $properties['Page affiliations'][0] ?? '';
		$language = $properties['Page language'][0] ?? 'en';
		//return $properties; // Uncomment to debug

		$yaml = [

			// Manifest metadata
			// @see https://standards.internetofproduction.org/pub/okh#manifest-metadata
			'date-created' => $dateCreated,
			'date-updated' => $dateUpdated,
			'manifest-author' => [
				'name' => 'Appropedia',
				'email' => 'admin@appropedia.org'
			],

			// Descriptive properties
			// @see https://standards.internetofproduction.org/pub/okh#descriptive-properties
			'title' => str_replace( '_', ' ', $title ),
			'description' => $extract,
			'intended-use' => $uses,
			'keywords' => $keywords,
			'project-link' => 'https://www.appropedia.org/' . str_replace( ' ', '_', $title ),
			'contact' => [
				'name' => $author,
				'social' => [
					'platform' => 'Appropedia',
					'user-handle' => $author,
				]
			],
			'version' => $version,
			'development-stage' => $status,
			'made' => $made === 't',
			'image' => $image,
			'variant-of' => [
				'title' => $type,
				'web' => $type ? 'https://www.appropedia.org/' . str_replace( ' ', '_', $type ) : null,
			],
			'license' => [
				'documentation' => $license,
			],
			'licensor' => [
				'name' => $author,
				'affliation' => $affiliation,
				'contact' => 'https://www.appropedia.org/' . str_replace( ' ', '_', $author ),
			],

			// Documentation
			// @see https://standards.internetofproduction.org/pub/okh#documentation
			'documentation-home' => 'https://www.appropedia.org/' . str_replace( ' ', '_', $title ),

			// Language and translation
			// @see https://standards.internetofproduction.org/pub/okh#language-and-translation
			'documentation-language' => $language,

		];

		// Remove null and empty values
		$yaml = self::array_filter_recursive( $yaml );

		// Return the YAML file
		$dump = Yaml::dump( $yaml, 3 );
		$response = $this->getResponseFactory()->create();
		$response->setHeader( 'Content-Type', 'text/yaml; charset=utf-8' );
		$response->setHeader( 'Content-Disposition', 'attachment; filename=' . $title . '.yaml' );
		$response->getBody()->write( $dump );
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

	private static function array_filter_recursive( array $array ): array {
		foreach ( $array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = self::array_filter_recursive( $value );
			}
			if ( $value === null || $value === '' || $value === [] ) {
				unset( $array[ $key ] );
			}
		}
		return $array;
	}
}