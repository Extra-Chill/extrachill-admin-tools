/**
 * ToolTabs Component
 *
 * Tab navigation for available tools.
 */

import { Tabs } from '@extrachill/components';
import { useAdminTools } from '../context/AdminToolsContext';

export default function ToolTabs() {
	const { availableTools, activeTool, setActiveTool } = useAdminTools();

	if ( availableTools.length === 0 ) {
		return null;
	}

	return (
		<Tabs
			tabs={ availableTools.map( ( tool ) => ( { id: tool.id, label: tool.title } ) ) }
			active={ activeTool || availableTools[ 0 ]?.id }
			onChange={ setActiveTool }
			className="ec-admin-tools__tabs"
			classPrefix="ec-admin-tools"
		/>
	);
}
