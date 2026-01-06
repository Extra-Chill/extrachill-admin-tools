/**
 * Taxonomy Sync Tool
 *
 * Sync taxonomies from main site to other network sites.
 */

import { useState } from '@wordpress/element';
import { CheckboxControl, Button, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import { syncTaxonomies } from '../api/client';
import { getConfig } from '../api/client';

const TAXONOMIES = [
	{ slug: 'location', label: 'Location (Hierarchical)' },
	{ slug: 'festival', label: 'Festival' },
	{ slug: 'artist', label: 'Artist' },
	{ slug: 'venue', label: 'Venue' },
];

export default function TaxonomySync() {
	const { addNotice, sites } = useAdminTools();
	const config = getConfig();
	const mainSiteId = config.defaultSiteId || 1;

	const targetSites = sites.filter( ( s ) => s.id !== mainSiteId );

	const [ selectedTaxonomies, setSelectedTaxonomies ] = useState(
		TAXONOMIES.map( ( t ) => t.slug )
	);
	const [ selectedSites, setSelectedSites ] = useState(
		targetSites.map( ( s ) => s.id )
	);
	const [ isSyncing, setIsSyncing ] = useState( false );
	const [ report, setReport ] = useState( null );

	const handleTaxonomyChange = ( slug, checked ) => {
		if ( checked ) {
			setSelectedTaxonomies( [ ...selectedTaxonomies, slug ] );
		} else {
			setSelectedTaxonomies( selectedTaxonomies.filter( ( t ) => t !== slug ) );
		}
	};

	const handleSiteChange = ( siteId, checked ) => {
		if ( checked ) {
			setSelectedSites( [ ...selectedSites, siteId ] );
		} else {
			setSelectedSites( selectedSites.filter( ( s ) => s !== siteId ) );
		}
	};

	const handleSync = async () => {
		if ( selectedTaxonomies.length === 0 ) {
			addNotice( 'error', 'Please select at least one taxonomy.' );
			return;
		}
		if ( selectedSites.length === 0 ) {
			addNotice( 'error', 'Please select at least one target site.' );
			return;
		}

		setIsSyncing( true );
		setReport( null );

		try {
			const response = await syncTaxonomies( selectedTaxonomies, selectedSites );
			setReport( response );
			addNotice( 'success', 'Taxonomy sync completed.' );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to sync taxonomies.' );
		} finally {
			setIsSyncing( false );
		}
	};

	return (
		<div className="ec-tool ec-tool--taxonomy-sync">
			<div className="ec-tool__description">
				<p>
					Synchronize taxonomies from the main site (extrachill.com) to other
					network sites.
				</p>
			</div>

			<div className="ec-tool__section">
				<h3>Select Target Sites</h3>
				<p>Choose which sites should receive taxonomies from the main site:</p>
				{ targetSites.map( ( site ) => (
					<CheckboxControl
						key={ site.id }
						label={ `${ site.name } (Blog ID ${ site.id })` }
						checked={ selectedSites.includes( site.id ) }
						onChange={ ( checked ) => handleSiteChange( site.id, checked ) }
						__nextHasNoMarginBottom
					/>
				) ) }
			</div>

			<div className="ec-tool__section">
				<h3>Select Taxonomies</h3>
				<p>Choose which taxonomies to sync:</p>
				{ TAXONOMIES.map( ( tax ) => (
					<CheckboxControl
						key={ tax.slug }
						label={ tax.label }
						checked={ selectedTaxonomies.includes( tax.slug ) }
						onChange={ ( checked ) => handleTaxonomyChange( tax.slug, checked ) }
						__nextHasNoMarginBottom
					/>
				) ) }
			</div>

			<div className="ec-tool__actions">
				<Button
					variant="primary"
					onClick={ handleSync }
					disabled={ isSyncing }
				>
					{ isSyncing ? (
						<>
							<Spinner /> Syncing...
						</>
					) : (
						'Sync Taxonomies'
					) }
				</Button>
			</div>

			{ report && (
				<div className="ec-tool__report">
					<h3>Sync Report</h3>
					<pre>{ JSON.stringify( report, null, 2 ) }</pre>
				</div>
			) }
		</div>
	);
}
