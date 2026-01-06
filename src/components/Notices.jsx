/**
 * Notices Component
 *
 * Displays success/error notifications.
 */

import { Notice } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';

export default function Notices() {
	const { notices, removeNotice } = useAdminTools();

	if ( notices.length === 0 ) {
		return null;
	}

	return (
		<div className="ec-admin-tools__notices">
			{ notices.map( ( notice ) => (
				<Notice
					key={ notice.id }
					status={ notice.type }
					onRemove={ () => removeNotice( notice.id ) }
					isDismissible
				>
					{ notice.message }
				</Notice>
			) ) }
		</div>
	);
}
