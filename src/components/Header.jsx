/**
 * Header Component
 *
 * Displays title and site selector dropdown.
 */

import { SelectControl } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';

export default function Header() {
	const { sites, selectedSiteId, switchSite } = useAdminTools();

	const siteOptions = sites.map( ( site ) => ( {
		label: `${ site.name } (${ site.domain }${ site.path })`,
		value: site.id,
	} ) );

	return (
		<div className="ec-admin-tools__header">
			<h1>Extra Chill Admin Tools</h1>
			<div className="ec-admin-tools__site-selector">
				<SelectControl
					label="Site Context"
					value={ selectedSiteId }
					options={ siteOptions }
					onChange={ ( value ) => switchSite( parseInt( value, 10 ) ) }
					__nextHasNoMarginBottom
				/>
				<span className="ec-admin-tools__site-hint">
					Tools below will operate on the selected site.
				</span>
			</div>
		</div>
	);
}
