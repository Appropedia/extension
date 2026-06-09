<?php

/**
 * This script takes a project title
 * and validates a list of parameters to submit
 * to the OSHWA Certification API
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

require 'vendor/autoload.php';

if ( $_POST ) {
	$message = [
		'responsiblePartyType' => $_POST['responsiblePartyType'],
		'responsibleParty' => $_POST['responsibleParty'],
		'bindingParty' => $_POST['bindingParty'],
		'country' => $_POST['country'],
		'streetAddress1' => $_POST['streetAddress1'],
		'streetAddress2' => $_POST['streetAddress2'],
		'city' => $_POST['city'],
		'state' => $_POST['state'],
		'postalCode' => $_POST['postalCode'],
		'privateContact' => $_POST['privateContact'],
		'publicContact' => $_POST['publicContact'],
		'projectName' => $_POST['projectName'],
		'projectWebsite' => stripcslashes($_POST['projectWebsite']),
		'projectVersion' => $_POST['projectVersion'],
		'previousVersions' => [ '', '' ],
		'projectDescription' => $_POST['projectDescription'],
		'primaryType' => $_POST['primaryType'],
		'additionalType' => explode(',', $_POST['additionalType']),
		'projectKeywords' => explode(',', $_POST['projectKeywords']),
		'citations' => [
			[ 'url' => $_POST['citationURL1'], 'title' => $_POST['citation1'] ],
			[ 'url' => $_POST['citationURL2'], 'title' => $_POST['citation2'] ],
			[ 'url' => $_POST['citationURL3'], 'title' => $_POST['citation3'] ]
		],
		'documentationUrl' => $_POST['documentationUrl'],
		'availableFileFormat' => isset( $_POST['availableFileFormat'] ) ? 'true' : 'false',
		'hardwareLicense' => $_POST['hardwareLicense'] ? $_POST['hardwareLicense'] : 'Other',
		'softwareLicense' => $_POST['softwareLicense'] ? $_POST['softwareLicense'] : 'No software',
		'documentationLicense' => $_POST['documentationLicense'],
		'noCommercialRestriction' => $_POST['noCommercialRestriction'],
		'explanationNcr' => $_POST['explanationNcr'],
		'noDocumentationRestriction' => $_POST['noDocumentationRestriction'],
		'explanationNdr' => $_POST['explanationNdr'],
		'openHardwareComponents' => $_POST['openHardwareComponents'],
		'explanationOhwc' => $_POST['explanationOhwc'],
		'creatorContribution' => $_POST['creatorContribution'],
		'explanationCcr' => $_POST['explanationCcr'],
		'noUseRestriction' => $_POST['noUseRestriction'],
		'explanationNur' => $_POST['explanationNur'],
		'redistributedWork' => $_POST['redistributedWork'],
		'explanationRwr' => $_POST['explanationRwr'],
		'noSpecificProduct' => $_POST['noSpecificProduct'],
		'explanationNsp' => $_POST['explanationNsp'],
		'noComponentRestriction' => $_POST['noComponentRestriction'],
		'explanationNor' => $_POST['explanationNor'],
		'technologyNeutral' => $_POST['technologyNeutral'],
		'explanationTn' => $_POST['explanationTn'],
		'certificationMarkTerms' => [
			[
				'accurateContactInformation' => [
					'term' => 'I have provided OSHWA with accurate contact information, recognize that all official communications from OSHWA will be directed to that contact information, and will update that contact information as necessary.',
					'agreement' => $_POST['accurateContactInformation'] == 1 ? 'true' : 'false',
				],
			],
			[
				'complianceWithOfficialCertificationGuidelines' => [
					'term' => 'I will only use the certification mark in compliance with official certification guidelines.',
					'agreement' => $_POST['complianceWithOfficialCertificationGuidelines'] == 1 ? 'true' : 'false',
				],
			],
			[
				'oshwaCertificationMark' => [
					'term' => 'I acknowledge that all right, title, and interest in the certification mark remains with OSHWA.',
					'agreement' => $_POST['oshwaCertificationMark'] == 1 ? 'true' : 'false',
				],
			],
			[
				'violationsEnforcement' => [
					'term' => 'I acknowledge that OSHWA has the right to enforce violations of the use of the mark. This enforcement may involve financial penalties for misuse in bad faith.',
					'agreement' => $_POST['violationsEnforcement'] == 1 ? 'true' : 'false',
				],
			],
			[
				'responsibility' => [
					'term' => 'I have the ability to bind those responsible for the certified item to this agreement.',
					'agreement' => $_POST['responsibility'] == 1 ? 'true' : 'false',
				],
			],
		],
		'explanationCertificationTerms' => $_POST['explanationCertificationTerms'],
		'relationship' => $_POST['relationship'],
		'agreementTerms' => $_POST['agreementTerms'] == 1 ? 'true' : 'false',
		'parentName' => $_POST['parentName'],
	];
	//echo '<pre>'; print_r( json_encode( $message, JSON_PRETTY_PRINT ) ); exit; // Uncomment to debug

	$post_url = 'https://certificationapi.oshwa.org/api/projects/';
	$curl = curl_init( $post_url );
	curl_setopt( $curl, CURLOPT_URL, $post_url );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	$headers = [
		'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjYzOWEyYWUzYmYzYjUwMDAxOGYzOTg2MiIsImlhdCI6MTY3MTA0ODEyOSwiZXhwIjoxNjc5Njg4MTI5fQ.5_BqE2I6j1lJbunXkMEXSjWk2QH9K1urnzQcOYknYmM',
		'Content-Type: application/json',
	];
	curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $curl, CURLOPT_POST, true );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $message ) );
	$post_response = curl_exec( $curl );
	//var_dump( $post_response ); // Uncomment to debug
	curl_close( $curl );
}

if ( !array_key_exists( 'title', $_GET ) ) {
	exit( 'Title required' );
}

// Extra "e" means "encoded"
$titlee = $_GET['title'];

// Basic decoding
$title = stripcslashes( str_replace( '_', ' ', $titlee ) );

// Connect to the Appropedia API
$api = new EasyWiki( 'https://www.appropedia.org/w/api.php' );

// Get timestamp
$params = [
	'titles' => $title,
	'prop' => 'revisions',
	'rvslots' => '*',
	'rvprop' => 'timestamp',
];
$result = $api->query( $params );
if ( !$result ) {
	exit( 'Page not found' );
}
//var_dump( $result ); exit; // Uncomment to debug
$timestamp = $api->find( 'timestamp', $result );
$timestamp = substr( $timestamp, 0, -10 );
if ( !$timestamp ) {
	exit( 'Page not found' );
}

// Get semantic properties
$properties = @file_get_contents( 'https://www.appropedia.org/w/rest.php/v1/page/' . urlencode( $titlee ) . '/semantic' );
if ( !$timestamp ) {
	exit( 'Semantic properties not found' );
}
$properties = json_decode( $properties, true );
$keywords = $properties['Keywords'] ?? '';
$affiliations = $properties['Affiliations'] ?? '';
$authors = $properties['Authors'] ?? '';
$projectAuthors = $properties['Project authors'] ?? '';
$status = $properties['Status'] ?? '';
$made = $properties['Made'] ?? '';
$uses = $properties['Uses'] ?? '';
$license = $properties['License'] ?? 'CC-BY-SA-4.0';
$hardwareLicense = $properties['Hardware license'] ?? 'CC-BY-SA-4.0';
$softwareLicense = $properties['Software license'] ?? '';
$location = $properties['Location'] ?? '';
$modDate = $properties['Modification date'] ?? '';
$URL = $properties['URL'] ?? '';

// Get extract
$params = [
	'titles' => $title,
	'prop' => 'extracts',
	'exintro' => true,
	'exsentences' => 5,
	'exlimit' => 1,
	'explaintext' => 1,
];
$result = $api->query( $params );
//var_dump( $result ); exit; // Uncomment to debug
$extract = $api->find( 'extract', $result );
$extract = str_replace( '"', "'", $extract );
$extract = str_replace( "\n", ' ', $extract );
$extract = trim( $extract );

// Get first author name
if ( empty( $authors ) and !empty( $projectAuthors ) ) {
	$authors = $projectAuthors;
}
$firstAuthor = explode( ',', $authors )[0];
$params = [
	'title' => $title,
	'action' => 'expandtemplates',
	'prop' => 'wikitext',
	'text' => '{{REALNAME:' . $firstAuthor . '}}',
];
$result = $api->get( $params );
//var_dump( $result ); exit; // Uncomment to debug
$firstAuthorName = $api->find( 'wikitext', $result );

// Get keywords
$keywords = strtolower( $keywords );

// Get revisions
$params = [
	'titles' => $title,
	'prop' => 'revisions',
	'rvprop' => 'ids|userid',
	'rvlimit' => 'max',
];
$result = $api->query( $params );
//var_dump( $result ); exit; // Uncomment to debug
$revisions = $api->find( 'revisions', $result );
$version = count( $revisions[0] );

// ResponsiblePartyType and ResponsibleParty
// If affiliation is present, this will be selected as default.
if ( $affiliations ) {
	$responsiblePartyType = 'Organization';
	$notResponsiblePartyType = 'Individual';
	$bindingParty = $firstAuthorName;
	$responsibleParty = explode( ',', $affiliations )[0];
} else {
	$responsiblePartyType = 'Individual';
	$notResponsiblePartyType = 'Organization';
	$bindingParty = '';
	$responsibleParty = $firstAuthor;
}

// Get country
// https://stackoverflow.com/questions/21439272/file-get-contents-returns-404-when-url-is-opened-with-the-browser-and-url-is-val
// Some browser spoofing to not be blocked by the API
function get_data( $url ) {
	$curl = curl_init( $url );
	curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13' );
	curl_setopt( $curl, CURLOPT_FAILONERROR, true );
	curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $curl, CURLOPT_AUTOREFERER, true );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );
	$data = curl_exec( $curl );
	if ( !$data ) {
		exit( 'cURL error number:' . curl_errno( $curl ) . ' cURL error:' . curl_error( $curl ) );
	}
	$data = json_decode( $data, true );
	return $data;
}

if ( preg_match( '/-?\d{1,2}\.\d+\s-?\d{1,2}\.\d+/', $location ) ) {
	$latLon = explode( ' ', $location );
	$url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' . $latLon[0] . '&lon=' . $latLon[1];
	try {
		$locationInfo = get_data( $url );
	} finally {
		$country = $locationInfo[0]['address']['country'];
		$state = $locationInfo[0]['address']['state'];
		$postalCode = $locationInfo[0]['address']['postcode'] ?? 'NA';
	}
} else {
	$location = str_replace( ' ', '+', $location );
	$url = 'https://nominatim.openstreetmap.org/search.php?addressdetails=1&hierarchy=0&group_hierarchy=1&format=jsonv2&q=' . $location;
	try {
		$locationInfo = get_data( $url );
	} finally {
		$country = $locationInfo[0]['address']['country'] ?? '';
		$state = $locationInfo[0]['address']['state'] ?? '';
		$postCode = $locationInfo[0]['address']['postcode'] ?? '';
		// https://wiki.openstreetmap.org/wiki/Proposed_features/new_place_values
		// place = population
		// city = 100,000+
		// town = 10000 to 100,000
		// village = < 10,000
		// hamlet = < 100
		if ( isset( $locationInfo[0]['address']['place'] ) ) {
			$place = $locationInfo[0]['address']['place'];
		} else if ( isset($locationInfo[0]['address']['city'] ) ) {
			$place = $locationInfo[0]['address']['city'];
		} else if ( isset($locationInfo[0]['address']['town'] ) ) {
			$place = $locationInfo[0]['address']['town'];
		} else if ( isset($locationInfo[0]['address']['county'] ) ) {
			$place = $locationInfo[0]['address']['county'];
		} else if ( isset($locationInfo[0]['address']['village'] ) ) {
			$place = $locationInfo[0]['address']['village'];
		} else if ( isset($locationInfo[0]['address']['hamlet'] ) ) {
			$place = $locationInfo[0]['address']['hamlet'];
		}
	}
}

// All countries
if ( $country === 'United States' ) {
	$country = 'United States of America';
}
$countries_list = [ 'Afghanistan', 'Aland Islands', 'Albania', 'Algeria', 'American Samoa', 'Andorra', 'Angola', 'Anguilla', 'Antarctica', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Aruba', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bermuda', 'Bhutan', 'Bolivia', 'Bonaire, Sint Eustatius and Saba', 'Bosnia and Herzegovina', 'Botswana', 'Bouvet Island', 'Brazil', 'British Indian Ocean Territory', 'Brunei Darussalam', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cambodia', 'Cameroon', 'Canada', 'Cape Verde', 'Cayman Islands', 'Central African Republic', 'Chad', 'Chile', 'China', 'Christmas Island', 'Cocos (Keeling) Islands', 'Colombia', 'Comoros', 'Congo', 'Congo, Democratic Republic of the Congo', 'Cook Islands', 'Costa Rica', "Cote D'Ivoire", 'Croatia', 'Cuba', 'Curacao', 'Cyprus', 'Czech Republic', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Ethiopia', 'Falkland Islands (Malvinas)', 'Faroe Islands', 'Fiji', 'Finland', 'France', 'French Guiana', 'French Polynesia', 'French Southern Territories', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Gibraltar', 'Greece', 'Greenland', 'Grenada', 'Guadeloupe', 'Guam', 'Guatemala', 'Guernsey', 'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti', 'Heard Island and Mcdonald Islands', 'Holy See (Vatican City State)', 'Honduras', 'Hong Kong', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran, Islamic Republic of', 'Iraq', 'Ireland', 'Isle of Man', 'Israel', 'Italy', 'Jamaica', 'Japan', 'Jersey', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', "Korea, Democratic People's Republic of", 'Korea, Republic of', 'Kosovo', 'Kuwait', 'Kyrgyzstan', "Lao People's Democratic Republic", 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libyan Arab Jamahiriya', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Macao', 'Macedonia, the Former Yugoslav Republic of', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Martinique', 'Mauritania', 'Mauritius', 'Mayotte', 'Mexico', 'Micronesia, Federated States of', 'Moldova, Republic of', 'Monaco', 'Mongolia', 'Montenegro', 'Montserrat', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'Netherlands Antilles', 'New Caledonia', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'Niue', 'Norfolk Island', 'Northern Mariana Islands', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Palestinian Territory, Occupied', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Pitcairn', 'Poland', 'Portugal', 'Puerto Rico', 'Qatar', 'Reunion', 'Romania', 'Russian Federation', 'Rwanda', 'Saint Barthelemy', 'Saint Helena', 'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Martin', 'Saint Pierre and Miquelon', 'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Serbia and Montenegro', 'Seychelles', 'Sierra Leone', 'Singapore', 'Sint Maarten', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Georgia and the South Sandwich Islands', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Svalbard and Jan Mayen', 'Swaziland', 'Sweden', 'Switzerland', 'Syrian Arab Republic', 'Taiwan, Province of China', 'Tajikistan', 'Tanzania, United Republic of', 'Thailand', 'Timor-Leste', 'Togo', 'Tokelau', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Turks and Caicos Islands', 'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States of America', 'United States Minor Outlying Islands', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Venezuela', 'Viet Nam', 'Virgin Islands, British', 'Virgin Islands, U.s.', 'Wallis and Futuna', 'Western Sahara', 'Yemen', 'Zambia', 'Zimbabwe' ];
foreach ( $countries_list as $key => $value ) {
	if ( $value === $country ) {
		$country_key = $key;
		unset( $countries_list[ $key ] );
	}
}

// Uses validation
$OSHWAvalidUses = '3D Printing, Agriculture, Arts, Education, Electronics, Enclosure, Environmental, Home Connection, IOT, Manufacturing, Robotics, Science, Sound, Space, Tool, Wearables';
$OSHWAvalidUses = explode( ',', $OSHWAvalidUses );
$OSHWAvalidUses = array_map( 'trim', $OSHWAvalidUses );
$OSHWAvalidUses = array_map( 'ucwords', $OSHWAvalidUses );
$usesArray = array_map( 'trim', array_map( 'ucwords', explode( ',', $uses ) ) );
$validUses = array_intersect( $usesArray, $OSHWAvalidUses );
$validUsesSize = count( $validUses );
if ( !$validUses ) {
	$primaryType = [];
	$secondaryTypes = [];
	$otherTypes = $validUses;
} else if ( $validUsesSize === 1 ) {
	$primaryType = array_shift( $validUses );
	$secondaryTypes = [];
	$otherTypes = array_diff( $OSHWAvalidUses, $primaryType );
} else if ( $validUsesSize === 2 ) {
	$primaryType = array_shift( $validUses );
	$secondaryTypes = array_shift( $validUses );
	$otherTypes = array_diff( $OSHWAvalidUses, $validUses );
} else if ( $validUsesSize > 2 ) {
	$primaryType = array_shift( $validUses );
	$secondaryTypes = $validUses;
	array_unshift( $validUses, $primaryType );
	$otherTypes = array_diff( $OSHWAvalidUses, $validUses );
};
foreach ( $OSHWAvalidUses as $key => $value ) {
	if ( $value === $primaryType ) {
		$primaryTypeKey = $key;
	}
}

?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>OSHWA</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
	<style>
		.small-middle-container {
			margin: auto;
			width: 60%;
		}
		.readonly, .readonly:active, .readonly:enabled {
			background:#eee;
		}
	</style>
</head>
<body><div class="container">

<h1>Certify a project</h1>

<p>This is a list of the metadata retrieved for project <strong><a href="<?php echo $URL; ?>"><?php echo $title; ?></a></strong>. Do the following:</p>
<ol>
<li>Review the parameters drawn from the documentation. If any has an error or is missing, go back to the <a href="<?php echo $URL; ?>&action=edit">documentation page and edit the appropriate template</a>.</li>
<li>Fill out the missing information in the <a href="">form</a> below or make any necessary changes.</li>
<li>Make the request by submitting the information at the end of the page.</li>
</ol>
<p>Please note that OSHWA will contact you directly regarding the status of your submission. This script makes a direct submission using the OSHWA API. Appropedia does not keep track of any applications and will not store copies of the information entered on this page.</p>
<hr>
<br>

<form method="post" action="https://www.appropedia.org/scripts/retrieveOshwaMetadata.php" class="small-middle-container">

<h3>Basic information — Section 1 of 4</h3>

<p>This part of the form asks for basic information about the responsible party for the project to be certified, such as the name of the individual, company, or organization certifying the project and contact information for any future correspondence from OSHWA regarding your certification.</p>

<div class="mb-4">
	<label for="responsiblePartyType" class="form-label">This certification is on behalf of a:</label>
	<select id="responsiblePartyType" name="responsiblePartyType" class="form-select form-select" aria-label=".form-select-lg example" required>
		<option class="readonly" value="" readonly>Select one</option>
		<option value="<?php echo $responsiblePartyType; ?>" selected><?php echo $responsiblePartyType; ?></option>
		<option value="<?php echo $notResponsiblePartyType; ?>"><?php echo $notResponsiblePartyType; ?></option>
	</select>
	<div id="responsiblePartyTypeHelp" class="form-text">Appropedia will select organization if there is an affiliation named on the documentation, but you can override this.</div>
</div>

<div class="mb-4 ms-3">
	<label for="responsibleParty" class="form-label">Name of Individual, Company, or Organization Responsible for the Certified Item</label>
	<input id="responsibleParty" name="responsibleParty" required type="text" class="form-control" aria-describedby="responsiblePartyHelp" value="<?php echo $responsibleParty; ?>">
	<div id="responsiblePartyHelp" class="form-text">If hardware is certified on behalf of a company or organization, the first item of the <tt>attribution</tt> parameter on <a href="/Template:Page_data" title="Template:Page data">Template:Page data</a>.</div>
</div>

<div class="mb-4 ms-3">
	<label for="bindingParty" class="form-label">If not an Individual, name of Individual with Authority to Bind the Company or Organization</label>
	<input id="bindingParty" name="bindingParty" type="text" class="form-control" aria-describedby="bindingPartyHelp" value="<?php echo isset($bindingParty) ? $bindingParty : ''; ?>">
	<div id="bindingPartyHelp" class="form-text">The first page author is selected by default.</div>
</div>

<div class="mb-5">
	<label for="country" class="form-label">Country</label>
	<select id="country" name="country" class="form-select form-select" aria-label=".form-select-lg example">
		<?php if ( $country ): ?>
		<option selected><?php echo $country; ?></option>
		<?php else: ?>
		<option selected readonly class="readonly" value="">Select a country</option>
		<?php endif; ?>
		<?php foreach ( $countries_list as $c ): ?>
		<option><?php echo $c; ?></option>
		<?php endforeach; ?>
	</select>
	<div id="countryHelp" name="countryHelp" class="form-text">Country (and other location information) is calculated from the metadata added to the page, if available.</div>
</div>

<div class="mb-0">
	<label for="streetAddress1" class="form-label">Street Address Line 1</label>
	<input id="streetAddress1" name="streetAddress1" type="text" class="form-control" aria-describedby="streetAddress1Help">
	<div id="streetAddress1Help" class="form-text"></div>
</div>

<div class="mb-5">
	<label for="streetAddress2" class="form-label">Street Address Line 2</label>
	<input id="streetAddress2" name="streetAddress2" type="text" class="form-control" aria-describedby="streetAddress2Help">
	<div id="streetAddress2Help" class="form-text"></div>
</div>

<div class="mb-5">
	<label for="city" class="form-label">City/Town/Village</label>
	<input id="city" name="city" type="text" class="form-control" aria-describedby="cityHelp" value="<?php echo isset($place) ? $place : ''; ?>">
	<div id="cityHelp" class="form-text">City (and other location information) is calculated from the metadata added to the page, if available.</div>
</div>

<div class="mb-5">
	<label for="state" class="form-label">State/Province/Region</label>
	<input id="state" name="state" type="text" class="form-control" aria-describedby="stateHelp" value="<?php echo isset($state) ? $state : ''; ?>">
	<div id="stateHelp" class="form-text">State (and other location information) is calculated from the metadata added to the page, if available.</div>
</div>

<div class="mb-5">
	<label for="postalCode" class="form-label">Zip or postal code</label>
	<input id="postalCode" name="postalCode" type="text" class="form-control" aria-describedby="postalCodeHelp" value="<?php echo isset($postCode) ? $postCode : ''; ?>">
	<div id="postalCodeHelp" class="form-text">Postal code (and other location information) is calculated from the metadata added to the page, if available.</div>
</div>

<div class="mb-5">
	<label for="privateContact" class="form-label">OSHWA Contact Email Address</label>
	<input id="privateContact" name="privateContact" type="text" class="form-control" aria-describedby="privateContactHelp">
	<div id="privateContactHelp" class="form-text">This email address will be kept private and only used for official communications from OSHWA about your certification. <strong>Appropedia will not keep a copy of this.</strong></div>
</div>

<div class="mb-5">
	<label for="publicContact" class="form-label">Public contact email address</label>
	<input id="publicContact" name="publicContact" type="text" class="form-control" aria-describedby="publicContactHelp">
	<div id="publicContactHelp" class="form-text">This address will be made publicly available in the certification directory.</div>
</div>

<h3>Project information — Section 2 of 4</h3>

<p>This part of the form asks for information about the project you wish to certify. Information submitted here will appear on your project’s profile listing.</p>

<div class="mb-5 row">
	<div class="col">
		<label for="projectName" class="form-label">Project name</label>
		<input id="projectName" name="projectName" type="text" required class="form-control readonly" aria-describedby="projectNameHelp" value="<?php echo isset($title) ? $title : ''; ?>" readonly>
		<div id="projectNameHelp" class="form-text">Defaults to the Appropedia project page title. If you wish to rename it, consider moving the page.</div>
	</div>
	<div class="col">
		<label for="projectVersion" class="form-label">Project version</label>
		<div class="input-group">
			<span class="input-group-text">v</span>
			<input id="projectVersion" name="projectVersion" type="text" class="form-control" aria-describedby="projectVersionHelp" value="<?php echo isset($version) ? $version : ''; ?>">
		</div>
		<div id="projectVersionHelp" class="form-text">This displays the edit count at the time of submission. Feel free to override this if you keep a different version control system.</div>
	</div>
</div>

<div class="mb-5">
	<label for="previousVersions" class="form-label">Have you previously registered a version(s) of your project with OSHWA?</label>
	<input id="previousVersions" name="previousVersions" type="text" class="form-control" aria-describedby="previousVersionsHelp" placeholder="Enter an existing OSHWA UID:">
	<div id="previousVersionsHelp" class="form-text">If you have previously registered a version(s) of your project with OSHWA, type in the UID for that project</div>
</div>

<div class="mb-5">
	<label for="projectDescription" class="form-label">Project description</label>
	<textarea id="projectDescription" name="projectDescription" class="form-control" aria-describedby="projectDescriptionHelp"><?php echo isset($extract) ? $extract : ''; ?></textarea>
	<div id="projectDescriptionHelp" class="form-text">Provide a brief description of your project (500 characters).</div>
</div>

<div class="mb-5">
	<label for="projectWebsite" class="form-label">Project website</label>
	<input id="projectWebsite" name="projectWebsite" type="url" pattern="http(s?)(:\/\/)((www.)?)(([^.]+)\.)?([a-zA-z0-9\-_]+)(.com|.net|.gov|.org)(\/[^\s]*)?" class="form-control" aria-describedby="projectWebsiteHelp" value="<?php echo isset($URL) ? $URL : ''; ?>">
	<div id="projectWebsiteHelp" class="form-text">This defaults to Appropedia's URL but you can override it if you want to point to a different documentation page. Include the protocol to your URL (e.g. http:// or https://)</div>
</div>

<div class="mb-5">
	<label for="primaryType" class="form-label">Primary project type</label>
	<select id="primaryType" name="primaryType" required class="form-select form-select" aria-label="">
		<?php if ( $primaryType ): ?>
		<option selected><?php echo $primaryType; ?></option>
		<?php else: ?>
		<option selected readonly class="readonly" value="">Select a type</option>
		<?php endif; ?>
		<?php foreach ( $otherTypes as $otherType ): ?>
		<option><?php echo $otherType; ?></option>
		<?php endforeach; ?>
	</select>
	<div id="primaryTypeHelp" name="primaryTypeHelp" class="form-text">This will search the <tt>uses</tt> parameter on Project data for the first occurrence of any of the following keywords: 3D Printing, Agriculture, Arts, Education, Electronics, Enclosure, Environmental, Home Connection, IOT, Manufacturing, Robotics, Science, Sound, Space, Tool, Wearables. If your project doesn't fall into any of these types, add "Other".</div>
</div>

<div class="mb-5">
	<label for="additionalType" class="form-label">Additional project types</label>
	<select id="additionalType" name="additionalType" class="form-select form-select" multiple aria-label="multiple">
		<?php foreach ( $secondaryTypes as $secondaryType ): ?>
		<option><?php echo $secondaryType; ?></option>
		<?php endforeach; ?>
		<?php foreach ( $otherTypes as $otherType ): ?>
		<option><?php echo $otherType; ?></option>
		<?php endforeach; ?>
	</select>
	<div id="additionalTypeHelp" class="form-text">This will search the <tt>uses</tt> parameter for any occurrence (other than the first) of any of the following keywords: 3D Printing, Agriculture, Arts, Education, Electronics, Enclosure, Environmental, Home Connection, IOT, Manufacturing, Robotics, Science, Sound, Space, Tool, Wearables. You can select more than one option (press CTRL to select multiple options).</div>
</div>

<div class="mb-5 col">
	<label for="projectKeywords" class="form-label">Project keywords</label>
	<input id="projectKeywords" name="projectKeywords" type="text" class="form-control" aria-describedby="projectKeywordsHelp" value="<?php echo isset($keywords) ? $keywords : ''; ?>">
	<div id="projectKeywordsHelp" class="form-text">If you would like your project to be searchable by specific keywords, add them here separated by commas.</div>
</div>

<div class="mb-5">
	<label for="documentationUrl" class="form-label">Where can the documentation be found for your project?</label>
	<input id="documentationUrl" name="documentationUrl" type="url" pattern="http(s?)(:\/\/)((www.)?)(([^.]+)\.)?([a-zA-z0-9\-_]+)(.com|.net|.gov|.org)(\/[^\s]*)?" class="form-control readonly" aria-describedby="documentationUrlHelp" value="<?php echo isset($URL) ? $URL : ''; ?>" readonly>
	<div id="documentationUrlHelp" class="form-text">Defaults to the Appropedia documentation page URL.</div>
</div>

<div class="mb-5">
	<input id="availableFileFormat" name="availableFileFormat" class="form-check-input" type="checkbox">
	<label for="availableFileFormat" class="form-label">All project documentation and design files are available in the preferred format for making changes.</label>
</div>

<div class="mb-0 row">
	<p>Does your project incorporate or build upon other open projects that are not currently certified by OSHWA? If so, use this space to cite those projects</p>
	<div class="col">
		<input id="citation1" name="citation1" type="text" class="form-control" aria-describedby="citation1Help" value="">
		<div id="citation1Help" class="form-text">Citation title</div>
	</div>
	<div class="col">
		<input id="citationURL1" name="citationURL1" type="url" pattern="http(s?)(:\/\/)((www.)?)(([^.]+)\.)?([a-zA-z0-9\-_]+)(.com|.net|.gov|.org|.in)(\/[^\s]*)?" class="form-control" aria-describedby="citationURL1Help" value="">
		<div id="citationURL1Help" class="form-text">Citation URL</div>
	</div>
</div>
<div class="mb-0 row">
	<div class="col">
		<input id="citation2" name="citation2" type="text" class="form-control" aria-describedby="citation2Help" value="">
		<div id="citation2Help" class="form-text">Citation title</div>
	</div>
	<div class="col">
		<input id="citationURL2" name="citationURL2" type="url" pattern="http(s?)(:\/\/)((www.)?)(([^.]+)\.)?([a-zA-z0-9\-_]+)(.com|.net|.gov|.org|.in)(\/[^\s]*)?" class="form-control" aria-describedby="citationURL2Help">
		<div id="citationURL2Help" class="form-text">Citation URL</div>
	</div>
</div>
<div class="mb-5 row">
	<div class="col">
		<input id="citation3" name="citation3" type="text" class="form-control" aria-describedby="citation1Help" value="">
		<div id="citation3Help" class="form-text">Citation title</div>
	</div>
	<div class="col">
		<input id="citationURL3" name="citationURL3" type="url" pattern="http(s?)(:\/\/)((www.)?)(([^.]+)\.)?([a-zA-z0-9\-_]+)(.com|.net|.gov|.org|.in)(\/[^\s]*)?" class="form-control" aria-describedby="citationURL3Help">
		<div id="citationURL3Help" class="form-text">Citation URL</div>
	</div>
</div>

<h3>Licensing information — Section 3 of 4</h3>

<p>This part of the form asks for information about your project’s licensing. In order to qualify for OSHWA certification, you must have chosen an open source license for your hardware, your software (if any), and your documentation. Your licenses for each of these elements will appear on your project’s profile listing.</p>

<div class="mb-5">
	<label for="hardwareLicense" class="form-label">What license is your project's hardware licensed under?</label>
	<input id="hardwareLicense" name="hardwareLicense" required type="text" class="form-control readonly" aria-describedby="hardwareLicenseHelp" value="<?php echo $hardwareLicense; ?>" readonly>
	<div id="hardwareLicenseHelp" class="form-text">Any changes to the hardware license must be done at the page.</div>
</div>

<div class="mb-5">
	<label for="softwareLicense" class="form-label">What license is your project's software licensed under? Select "No software" if your project doesn't use software</label>
	<input id="softwareLicense" name="softwareLicense" required type="text" class="form-control readonly" aria-describedby="softwareLicenseHelp" value="<?php echo $softwareLicense; ?>" readonly>
	<div id="softwareLicenseHelp" class="form-text">Any changes to the software license must be done at the page.</div>
</div>

<div class="mb-5">
	<label for="documentationLicense" class="form-label">What license is your project's documentation licensed under?</label>
	<input id="documentationLicense" name="documentationLicense" required type="text" class="form-control readonly" aria-describedby="documentationLicenseHelp" value="<?php echo $license; ?>" readonly>
	<div id="documentationLicenseHelp" class="form-text">Any changes to the documentation license must be done at the page.</div>
</div>

<div class="row">
	<div class="mb-5 col">The project is licensed in a way to allow for modifications and derivative works without commercial restriction.</div>
	<div class="mb-5 col">
		<select id="noCommercialRestriction" name="noCommercialRestriction" required class="form-select" aria-label="noCommercialRestriction">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationNcr" name="explanationNcr" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationNcrHelp"></textarea>
		<div id="explanationNcrHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">There is no restriction within my control to selling or giving away the project documentation.</div>
	<div class="mb-5 col">
		<select id="noDocumentationRestriction" name="noDocumentationRestriction" required class="form-select" aria-label="noDocumentationRestriction" id="noDocumentationRestriction" aria-describedby="noDocumentationRestrictionHelp">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
		<label for="noDocumentationRestriction" class="form-label"></label>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationNdr" name="explanationNdr" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationNdrHelp"></textarea>
		<div id="explanationNdrHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">Where possible, I have chosen to use components in my hardware that are openly licensed.</div>
	<div class="mb-5 col">
		<select id="openHardwareComponents" name="openHardwareComponents" required class="form-select" aria-label="openHardwareComponents">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationOhwc" name="explanationOhwc" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationOhwcHelp"></textarea>
		<div id="explanationOhwcHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">I understand and comply with the "Creator Contribution requirement," explained in the <a href="https://certification.oshwa.org/requirements.html">Requirements for Certification.</a></div>
	<div class="mb-5 col">
		<select id="creatorContribution" name="creatorContribution" required class="form-select" aria-label="creatorContribution">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationCcr" name="explanationCcr" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationCcrHelp"></textarea>
		<div id="explanationCcrHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">There is no restriction on the use by persons or groups, or by the field of endeavor.</div>
	<div class="mb-5 col">
		<select id="noUseRestriction" name="noUseRestriction" required class="form-select" aria-label="noUseRestriction">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationNur" name="explanationNur" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationNurHelp"></textarea>
		<div id="explanationNurHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">The rights granted by any license on the project applies to all whom the work is redistributed to.</div>
	<div class="mb-5 col">
		<select id="redistributedWork" name="redistributedWork" required class="form-select" aria-label="redistributedWork">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationRwr" name="explanationRwr" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationRwrHelp"></textarea>
		<div id="explanationRwrHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">The rights granted under any license on the project do not depend on the licensed work being part of a specific product.</div>
	<div class="mb-5 col">
		<select id="noSpecificProduct" name="noSpecificProduct" required class="form-select" aria-label="noSpecificProduct">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationNsp" name="explanationNsp" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationNspHelp"></textarea>
		<div id="explanationNspHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">The rights granted under any license on the project do not restrict other hardware or software, for example by requiring that all other hardware or software sold with the item be open source.</div>
	<div class="mb-5 col">
		<select id="noComponentRestriction" name="noComponentRestriction" required class="form-select" aria-label="noComponentRestriction">
		<option value="true" selected>Yes</option>
		<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
	<textarea id="explanationNor" name="explanationNor" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationNorHelp"></textarea>
		<div id="explanationNorHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<div class="row">
	<div class="mb-5 col">The rights granted under any license on the project are technology neutral.</div>
	<div class="mb-5 col">
		<select id="technologyNeutral" name="technologyNeutral" required class="form-select" aria-label="technologyNeutral">
			<option value="true" selected>Yes</option>
			<option value="false">No</option>
		</select>
	</div>
	<div class="mb-5 col">
		<textarea id="explanationTn" name="explanationTn" readonly class="form-control readonly" placeholder="Provide a brief explanation (500 characters)" aria-describedby="explanationTnHelp"></textarea>
		<div id="explanationTnHelp" class="form-text">If you answered no, please explay why</div>
	</div>
</div>

<h3>Certification — Section 4 of 4</h3>

<p>This part of the form asks you to certify that you agree to specific terms of the <a href="https://certification.oshwa.org/license-agreement">OSHWA Certification Mark License Agreement.</a></p>

You agree to the following terms:
<div class="mb-1 ms-3">
	<input id="accurateContactInformation" name="accurateContactInformation" required class="form-check-input" type="checkbox">
	<label for="accurateContactInformation" class="form-label">I have provided OSHWA with accurate contact information, recognize that all official communications from OSHWA will be directed to that contact information, and will update that contact information as necessary.</label>
</div>
<div class="mb-1 ms-3">
	<input id="complianceWithOfficialCertificationGuidelines" name="complianceWithOfficialCertificationGuidelines" required class="form-check-input" type="checkbox">
	<label for="complianceWithOfficialCertificationGuidelines" class="form-label">I will only use the certification mark in compliance with official certification guidelines.</label>
</div>
<div class="mb-1 ms-3">
	<input id="oshwaCertificationMark" name="oshwaCertificationMark"required class="form-check-input" type="checkbox">
	<label for="oshwaCertificationMark" class="form-label">I acknowledge that all right, title, and interest in the certification mark remains with OSHWA.</label>
</div>
<div class="mb-1 ms-3">
	<input id="violationsEnforcement" name="violationsEnforcement" required class="form-check-input" type="checkbox">
	<label for="violationsEnforcement" class="form-label">I acknowledge that OSHWA has the right to enforce violations of the use of the mark. This enforcement may involve financial penalties for misuse in bad faith.</label>
</div>
<div class="mb-5 ms-3">
	<input id="responsibility" name="responsibility" required class="form-check-input" type="checkbox">
	<label for="responsibility" class="form-label">I have the ability to bind those responsible for the certified item to this agreement.</label>
</div>

<div class="mb-5">
	<label for="explanationCertificationTerms" class="form-label">If you do not agree with any of the above terms, please explain.</label>
	<textarea id="explanationCertificationTerms" name="explanationCertificationTerms" class="form-control" aria-describedby="explanationCertificationTermsHelp"></textarea>
	<div id="explanationCertificationTermsHelp" class="form-text">Provide a brief explanation (500 characters)</div>
</div>

<div class="mb-5">
	<label for="relationship" class="form-label">Relationship to certified item</label>
	<input id="relationship" name="relationship" required type="text" class="form-control" aria-describedby="relationshipHelp" placeholder="I am the primary developer of the certified item.' or 'This is my personal project.">
	<div id="relationshipHelp" class="form-text">Briefly describe your relationship to the certified item.</div>
</div>

<div class="mb-5">
	<input id="agreementTerms" name="agreementTerms" required class="form-check-input" type="checkbox">
	<label for="agreementTerms" class="form-check-label" for="agreementTerms">I agree to the terms of the <a href="https://certification.oshwa.org/license-agreement">OSHWA Open Source Hardware Certification Mark License Agreement</a>, including the Requirements for Certification and Usage Guidelines incorporated by reference and including license terms that are not present in or conflict with this web form. I acknowledge that by agreeing to the terms of the OSHWA Open Source Hardware Certification Mark License Agreement that I am binding the entity listed to the License Agreement. I recognize that I will receive my unique identification number that allows me to promote my project as OSHWA Open Source Hardware Certified in compliance with the user guidelines via the email provided to OSHWA after submitting this form.</label>
</div>

<div class="mb-5">
	<label for="parentName" class="form-label">If you are the parent or legal guardian entering into this agreement on behalf of an individual under the age of 18, please provide your name to certify that you also agree to be bound by this agreement.</label>
	<input id="parentName" name="parentName" type="text" class="form-control" placeholder="Enter name of parent or legal guardian" aria-describedby="parentNameHelp">
</div>

<button type="submit" class="btn btn-primary">Submit</button>

</form>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
<script>
	function readWriteColor () { 
		allReadOnly = document.querySelectorAll("[readOnly]");
		for (let i = 0; i < allReadOnly.length; i++) {
			if (!allReadOnly[i].classList.contains("readonly")){
				console.log(allReadOnly[i].classList);
				allReadOnly[i].style.backgroundColor = "#eee";
				allReadOnly[i].classList.add('readonly'); 
			}
		}
			classReadOnly = document.getElementsByClassName("readonly");
		for (let i = 0; i < classReadOnly.length; i++) {
			if (!classReadOnly[i].hasAttribute("readonly")){
				classReadOnly[i].style.backgroundColor = "#fff";
				classReadOnly[i].classList.remove('readonly'); 
			}
		}
	}

	var drop0 = document.getElementById('noCommercialRestriction');
	var exp0 = document.getElementById('explanationNcr');
	var drop1 = document.getElementById('noDocumentationRestriction');
	var exp1 = document.getElementById('explanationNdr');
	var drop2 = document.getElementById('openHardwareComponents');
	var exp2 = document.getElementById('explanationOhwc');
	var drop3 = document.getElementById('creatorContribution');
	var exp3 = document.getElementById('explanationCcr');
	var drop4 = document.getElementById('noUseRestriction');
	var exp4 = document.getElementById('explanationNur');
	var drop5 = document.getElementById('redistributedWork');
	var exp5 = document.getElementById('explanationRwr');
	var drop6 = document.getElementById('noSpecificProduct');
	var exp6 = document.getElementById('explanationNsp');
	var drop7 = document.getElementById('noComponentRestriction');
	var exp7 = document.getElementById('explanationNor');
	var drop8 = document.getElementById('technologyNeutral');
	var exp8 = document.getElementById('explanationTn');

	var drops = [drop0, drop1, drop2, drop3, drop4, drop5, drop6, drop7, drop8];
	var exps = [exp0, exp1, exp2, exp3, exp4, exp5, exp6, exp7, exp8];

	for (let i = 0; i < 9; i++) { 
		drops[i].addEventListener("change", () => {
			if (drops[i].value == "false") {
			exps[i].readOnly = false;
			exps[i].classList.remove('readonly');
			exps[i].required = true;
			} else {
			exps[i].readOnly = true;
			exps[i].classList.add('readonly');
			exps[i].required = false;
			}
		});
		}
		
	var drop0 = document.getElementById('responsiblePartyType');
	var exp0 = document.getElementById('bindingParty');
	if (drop0.value == "Organization") {
		exp0.required = true;
	} else {
		exp0.required = false;
	}
	drop0.addEventListener("change", () => {
		if (drop0.value == "Organization") {
		exp0.required = true;
		} else {
			exp0.required = false;
		}
	});
</script>
</body>
</html>