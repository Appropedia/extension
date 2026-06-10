<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Stream;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;
use chillerlan\QRCode\QRCode;

/**
 * This endpoint returns a PDF containing a specified set of pages
 * @todo Switch from wkhtmltopdf to dompdf or mPDF or headless Chrome
 */
class AppropediaPDF extends SimpleHandler {

	public function run() {

		$params = $this->getValidatedParams();
		$pages = $params['pages'];
		$title = $params['title'];
		$subtitle = $params['subtitle'];
		$logo = $params['logo'];
		$qrpage = $params['qrpage'];

		$tempDir = wfTempDir();
		$coverPath = $tempDir . '/cover.html';
		$pdfPath = $tempDir . '/temp.pdf';

		$command = 'wkhtmltopdf';
		$command .= ' --print-media-type';
		$command .= ' --user-style-sheet ' . __DIR__ . '/resources/AppropediaPDF.css';
		$command .= ' --footer-center [page]';

		// Set the cover
		$html = '<!DOCTYPE HTML>';
		$html .= '<html>';
		$html .= '<head>';
		$html .= '<meta charset="utf-8">';
		$html .= '<title>' . $title . '</title>';
		$html .= '</head>';
		$html .= '<body id="cover">';
		$html .= '<header>';
		if ( $logo ) {
			$html .= '<img id="cover-logo" src="' . $logo . '" width="100" />';
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
		file_put_contents( $coverPath, $html );
		$command .= ' cover ' . $coverPath;

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
		$command .= ' ' . $pdfPath;

		// Make the PDF
		exec( $command );
		$pdfResource = fopen( $pdfPath, 'rb' );

		// Clean up
		unlink( $pdfPath );
		unlink( $coverPath );

		// Return the PDF
        $factory = $this->getResponseFactory();
        $response = $factory->createNoContent(); 
        $response->setStatus( 200 );
        $response->setHeader( 'Content-Type', 'application/pdf' );
        $response->setHeader( 'Content-Length', (string)filesize( $pdfPath ) );
        $response->setHeader( 'Content-Disposition', 'attachment; filename=' . $title . '.pdf' );
        $stream = new Stream( $pdfResource );
        $response->setBody( $stream );
		return $response;
	}

	/** @inheritDoc */
	public function needsWriteAccess() {
		return false;
	}

	/** @inheritDoc */
	public function getParamSettings() {
		return [
			'pages' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'title' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'subtitle' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			],
			'logo' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			],
			'qrpage' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string'
			]
		];
	}
}