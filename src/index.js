/**
 * Admin Tools - Entry Point
 *
 * Mounts the React admin tools app in the Network Admin.
 */

import { createRoot } from '@wordpress/element';
import { AdminToolsProvider } from './context/AdminToolsContext';
import App from './App';
import './styles/admin-tools.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'extrachill-admin-tools-root' );

	if ( ! container ) {
		return;
	}

	const root = createRoot( container );

	root.render(
		<AdminToolsProvider>
			<App />
		</AdminToolsProvider>
	);
} );
