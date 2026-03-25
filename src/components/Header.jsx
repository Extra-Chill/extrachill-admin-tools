/**
 * Header Component
 *
 * Displays title and site selector dropdown.
 */

import { SelectControl } from '@wordpress/components';
import { Panel, PanelHeader, FieldGroup } from '@extrachill/components';
import { useAdminTools } from '../context/AdminToolsContext';

export default function Header() {
	const { sites, selectedSiteId, switchSite } = useAdminTools();

	const siteOptions = sites.map( ( site ) => ( {
		label: `${ site.name } (${ site.domain }${ site.path })`,
		value: site.id,
	} ) );

	return (
		<Panel className="ec-admin-tools__header" compact>
			<PanelHeader title="Extra Chill Admin Tools" />
			<div className="ec-admin-tools__site-selector">
				<FieldGroup label="Site Context">
					<SelectControl
						label="Site Context"
						value={ selectedSiteId }
						options={ siteOptions }
						onChange={ ( value ) => switchSite( parseInt( value, 10 ) ) }
						__nextHasNoMarginBottom
					/>
				</FieldGroup>
				<span className="ec-admin-tools__site-hint">
					Tools below will operate on the selected site.
				</span>
			</div>
		</Panel>
	);
}
