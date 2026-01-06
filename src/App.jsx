/**
 * Admin Tools App
 *
 * Main application component for the network admin tools.
 */

import { useAdminTools } from './context/AdminToolsContext';
import Header from './components/Header';
import ToolTabs from './components/ToolTabs';
import Notices from './components/Notices';

import ErrorLogger from './tools/ErrorLogger';
import ArtistAccessRequests from './tools/ArtistAccessRequests';
import ArtistUserRelationships from './tools/ArtistUserRelationships';
import ForumTopicMigration from './tools/ForumTopicMigration';
import QRCodeGenerator from './tools/QRCodeGenerator';
import TagMigration from './tools/TagMigration';
import TaxonomySync from './tools/TaxonomySync';
import LifetimeMemberships from './tools/LifetimeMemberships';
import TeamMemberManagement from './tools/TeamMemberManagement';

const TOOL_COMPONENTS = {
	'error-logger': ErrorLogger,
	'artist-access-requests': ArtistAccessRequests,
	'artist-user-relationships': ArtistUserRelationships,
	'forum-topic-migration': ForumTopicMigration,
	'qr-code-generator': QRCodeGenerator,
	'tag-migration': TagMigration,
	'taxonomy-sync': TaxonomySync,
	'lifetime-memberships': LifetimeMemberships,
	'team-member-management': TeamMemberManagement,
};

export default function App() {
	const { availableTools, activeTool } = useAdminTools();

	const ActiveToolComponent = activeTool
		? TOOL_COMPONENTS[ activeTool ]
		: null;

	return (
		<div className="ec-admin-tools">
			<Header />
			<Notices />
			<ToolTabs />

			{ ! activeTool && availableTools.length > 0 && (
				<div className="ec-admin-tools__welcome">
					<p>Select a tool from the tabs above to get started.</p>
				</div>
			) }

			{ ! activeTool && availableTools.length === 0 && (
				<div className="ec-admin-tools__empty">
					<p>No tools available for the selected site.</p>
				</div>
			) }

			{ ActiveToolComponent && (
				<div className="ec-admin-tools__content">
					<ActiveToolComponent />
				</div>
			) }
		</div>
	);
}
