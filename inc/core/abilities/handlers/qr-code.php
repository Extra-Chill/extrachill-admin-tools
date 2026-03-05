<?php
/**
 * QR code ability handler.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generate QR code.
 *
 * @param array $input Input with url.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_generate_qr( $input ) {
	$autoloader = EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'vendor/autoload.php';
	if ( ! file_exists( $autoloader ) ) {
		return new WP_Error( 'dependency_missing', 'QR code vendor library not installed.' );
	}

	require_once $autoloader;

	if ( ! class_exists( '\\Endroid\\QrCode\\QrCode' ) ) {
		return new WP_Error( 'dependency_missing', 'Endroid QR Code library not available.' );
	}

	$qr_code = new \Endroid\QrCode\QrCode(
		data: $input['url'],
		encoding: new \Endroid\QrCode\Encoding\Encoding( 'UTF-8' ),
		errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
		size: 1000,
		margin: 40
	);

	$writer = new \Endroid\QrCode\Writer\PngWriter();
	$result = $writer->write( $qr_code );

	return array(
		'image'     => base64_encode( $result->getString() ),
		'mime_type' => $result->getMimeType(),
		'url'       => $input['url'],
	);
}
