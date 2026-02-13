/**
 * QR Code Generator Tool
 *
 * Generate print-ready QR codes for any URL.
 */

import { useState } from '@wordpress/element';
import { TextControl, Button, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import { generateQRCode } from '../api/client';

export default function QRCodeGenerator() {
	const { addNotice } = useAdminTools();
	const [ url, setUrl ] = useState( '' );
	const [ isGenerating, setIsGenerating ] = useState( false );
	const [ qrData, setQrData ] = useState( null );

	const handleGenerate = async () => {
		if ( ! url ) {
			addNotice( 'error', 'Please enter a URL.' );
			return;
		}

		setIsGenerating( true );
		setQrData( null );

		try {
			const response = await generateQRCode( url );
			setQrData( response );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to generate QR code.' );
		} finally {
			setIsGenerating( false );
		}
	};

	const handleDownload = () => {
		if ( ! qrData?.image_url ) {
			return;
		}

		const link = document.createElement( 'a' );
		link.href = qrData.image_url;
		link.download = 'qr-code.png';
		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
	};

	return (
		<div className="ec-tool ec-tool--qr-generator">
			<div className="ec-tool__description">
				<p>Generate high-resolution print-ready QR codes for any URL.</p>
			</div>

			<div className="ec-tool__form">
				<TextControl
					label="Enter URL"
					help="Paste any URL (extrachill.com pages, extrachill.link pages, external websites, etc.)"
					value={ url }
					onChange={ setUrl }
					placeholder="https://example.com"
					type="url"
					__nextHasNoMarginBottom
				/>
				<Button
					variant="primary"
					onClick={ handleGenerate }
					disabled={ isGenerating || ! url }
				>
					{ isGenerating ? (
						<>
							<Spinner /> Generating...
						</>
					) : (
						'Generate QR Code'
					) }
				</Button>
			</div>

			{ qrData?.image_url && (
				<div className="ec-tool__result">
					<h3>Generated QR Code</h3>
					<p className="ec-tool__hint">
						Preview (scaled for display) - Download for full 1000x1000px print-ready resolution
					</p>
					<div className="ec-tool__qr-preview">
						<img
							src={ qrData.image_url }
							alt="Generated QR Code"
							style={ { maxWidth: '300px', height: 'auto' } }
						/>
					</div>
					<Button variant="primary" onClick={ handleDownload }>
						Download PNG (1000x1000px)
					</Button>
				</div>
			) }
		</div>
	);
}
