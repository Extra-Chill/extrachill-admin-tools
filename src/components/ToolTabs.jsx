/**
 * ToolTabs Component
 *
 * Tab navigation for available tools.
 */

import { Button } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';

export default function ToolTabs() {
	const { availableTools, activeTool, setActiveTool } = useAdminTools();

	if ( availableTools.length === 0 ) {
		return null;
	}

	return (
		<div className="ec-admin-tools__tabs">
			{ availableTools.map( ( tool ) => (
				<Button
					key={ tool.id }
					variant={ activeTool === tool.id ? 'primary' : 'secondary' }
					onClick={ () => setActiveTool( tool.id ) }
					className="ec-admin-tools__tab"
				>
					{ tool.title }
				</Button>
			) ) }
		</div>
	);
}
