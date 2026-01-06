/**
 * 404 Error Logger Tool
 *
 * Toggle 404 logging and view today's error count.
 */

import { useState, useEffect } from '@wordpress/element';
import { ToggleControl, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import {
	get404LoggerSettings,
	update404LoggerSettings,
	get404LoggerStats,
} from '../api/client';

export default function ErrorLogger() {
	const { addNotice } = useAdminTools();
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ enabled, setEnabled ] = useState( false );
	const [ stats, setStats ] = useState( { today_count: 0 } );

	useEffect( () => {
		loadData();
	}, [] );

	const loadData = async () => {
		setIsLoading( true );
		try {
			const [ settingsRes, statsRes ] = await Promise.all( [
				get404LoggerSettings(),
				get404LoggerStats(),
			] );
			setEnabled( settingsRes.enabled );
			setStats( statsRes );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to load settings.' );
		} finally {
			setIsLoading( false );
		}
	};

	const handleToggle = async ( newValue ) => {
		setIsSaving( true );
		try {
			await update404LoggerSettings( newValue );
			setEnabled( newValue );
			addNotice(
				'success',
				`404 logging ${ newValue ? 'enabled' : 'disabled' } network-wide.`
			);
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to update settings.' );
		} finally {
			setIsSaving( false );
		}
	};

	if ( isLoading ) {
		return (
			<div className="ec-tool ec-tool--error-logger">
				<Spinner />
				<span>Loading settings...</span>
			</div>
		);
	}

	return (
		<div className="ec-tool ec-tool--error-logger">
			<div className="ec-tool__description">
				<p>
					Logs 404 errors network-wide and sends daily email reports.
					Data is automatically cleaned up after sending.
				</p>
			</div>

			<div className="ec-tool__form">
				<ToggleControl
					label="Enable 404 Error Logging (Network-Wide)"
					checked={ enabled }
					onChange={ handleToggle }
					disabled={ isSaving }
					__nextHasNoMarginBottom
				/>
			</div>

			{ enabled && (
				<div className="ec-tool__stats">
					<p>
						<strong>Today's 404 errors across all sites:</strong>{ ' ' }
						{ stats.today_count }
					</p>
				</div>
			) }
		</div>
	);
}
