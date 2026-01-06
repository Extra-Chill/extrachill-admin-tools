/**
 * AdminToolsContext
 *
 * Provides state management for the admin tools app.
 * Manages site selection and computes available tools based on selected site.
 */

import {
	createContext,
	useContext,
	useState,
	useMemo,
	useCallback,
} from '@wordpress/element';
import { getConfig } from '../api/client';

const AdminToolsContext = createContext( null );

export function AdminToolsProvider( { children } ) {
	const config = getConfig();
	const sites = config.sites || [];
	const allTools = config.tools || [];
	const defaultSiteId = config.defaultSiteId || ( sites[ 0 ]?.id ?? 1 );

	const [ selectedSiteId, setSelectedSiteId ] = useState( defaultSiteId );
	const [ activeTool, setActiveTool ] = useState( null );
	const [ notices, setNotices ] = useState( [] );

	const selectedSite = useMemo(
		() => sites.find( ( s ) => s.id === selectedSiteId ) || sites[ 0 ],
		[ sites, selectedSiteId ]
	);

	const availableTools = useMemo( () => {
		return allTools.filter( ( tool ) => {
			if ( tool.sites === 'all' ) {
				return true;
			}
			if ( Array.isArray( tool.sites ) ) {
				return tool.sites.includes( selectedSiteId );
			}
			return false;
		} );
	}, [ allTools, selectedSiteId ] );

	const switchSite = useCallback(
		( newSiteId ) => {
			setSelectedSiteId( newSiteId );
			setActiveTool( null );
		},
		[]
	);

	const addNotice = useCallback( ( type, message ) => {
		const id = Date.now();
		setNotices( ( prev ) => [ ...prev, { id, type, message } ] );
		setTimeout( () => {
			setNotices( ( prev ) => prev.filter( ( n ) => n.id !== id ) );
		}, 5000 );
	}, [] );

	const removeNotice = useCallback( ( id ) => {
		setNotices( ( prev ) => prev.filter( ( n ) => n.id !== id ) );
	}, [] );

	const value = useMemo(
		() => ( {
			sites,
			selectedSiteId,
			selectedSite,
			switchSite,
			availableTools,
			activeTool,
			setActiveTool,
			notices,
			addNotice,
			removeNotice,
			restUrl: config.restUrl,
			nonce: config.nonce,
		} ),
		[
			sites,
			selectedSiteId,
			selectedSite,
			switchSite,
			availableTools,
			activeTool,
			notices,
			addNotice,
			removeNotice,
			config.restUrl,
			config.nonce,
		]
	);

	return (
		<AdminToolsContext.Provider value={ value }>
			{ children }
		</AdminToolsContext.Provider>
	);
}

export function useAdminTools() {
	const context = useContext( AdminToolsContext );
	if ( ! context ) {
		throw new Error( 'useAdminTools must be used within AdminToolsProvider' );
	}
	return context;
}

export default AdminToolsContext;
