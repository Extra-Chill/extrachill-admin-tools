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
 * @param array $input Input with url and optional size.
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

	$size = isset( $input['size'] ) ? absint( $input['size'] ) : 1000;
	if ( $size < 100 ) {
		$size = 100;
	}
	if ( $size > 2000 ) {
		$size = 2000;
	}

	$qr_code = new \Endroid\QrCode\QrCode(
		data: $input['url'],
		encoding: new \Endroid\QrCode\Encoding\Encoding( 'UTF-8' ),
		errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
		size: $size,
		margin: 40
	);

	$writer = new \Endroid\QrCode\Writer\PngWriter();
	$result = $writer->write( $qr_code );

	return array(
		'image'     => base64_encode( $result->getString() ),
		'mime_type' => $result->getMimeType(),
		'url'       => $input['url'],
		'size'      => $size,
	);
}
