/**
 * Team Member Management Tool
 *
 * Sync team members from main site and manage manual overrides.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, SelectControl, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import {
	getTeamMembers,
	syncTeamMembers,
	updateTeamMemberOverride,
} from '../api/client';
import { DataTable, SearchBox, Pagination } from '@extrachill/components';

export default function TeamMemberManagement() {
	const { addNotice } = useAdminTools();
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSyncing, setIsSyncing ] = useState( false );
	const [ users, setUsers ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalUsers, setTotalUsers ] = useState( 0 );
	const [ syncReport, setSyncReport ] = useState( null );

	const loadUsers = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await getTeamMembers( search, page );
			setUsers( response.users || [] );
			setTotalPages( response.total_pages || 1 );
			setTotalUsers( response.total || 0 );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to load users.' );
		} finally {
			setIsLoading( false );
		}
	}, [ search, page, addNotice ] );

	useEffect( () => {
		loadUsers();
	}, [ loadUsers ] );

	const handleSync = async () => {
		setIsSyncing( true );
		setSyncReport( null );
		try {
			const response = await syncTeamMembers();
			setSyncReport( response );
			addNotice( 'success', 'Team members synced successfully.' );
			loadUsers();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to sync team members.' );
		} finally {
			setIsSyncing( false );
		}
	};

	const handleAction = async ( userId, action ) => {
		try {
			await updateTeamMemberOverride( userId, action );
			addNotice( 'success', 'User updated successfully.' );
			loadUsers();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to update user.' );
		}
	};

	const columns = [
		{ key: 'user_login', label: 'Username' },
		{ key: 'user_email', label: 'Email' },
		{
			key: 'is_team_member',
			label: 'Team Member',
			render: ( value ) => (
				<span className={ `ec-badge ec-badge--${ value ? 'yes' : 'no' }` }>
					{ value ? 'Yes' : 'No' }
				</span>
			),
		},
		{
			key: 'actions',
			label: 'Actions',
			render: ( _, row ) => (
				<SelectControl
					value=""
					options={ [
						{ label: '-- Select Action --', value: '' },
						{ label: 'Grant Team Role', value: 'force_add' },
						{ label: 'Revoke Team Role', value: 'force_remove' },
					] }
					onChange={ ( action ) => {
						if ( action ) {
							handleAction( row.ID, action );
						}
					} }
					__nextHasNoMarginBottom
				/>
			),
		},
	];

	return (
		<div className="ec-tool ec-tool--team-management">
			<div className="ec-tool__description">
				<p>
					Grant or revoke the <code>extra_chill_team</code> WordPress
					role network-wide. The role is the source of truth for team
					membership — granting it gives the user real WP capabilities
					(upload_files, edit_posts, access_studio, etc.) on every
					subsite in the Extra Chill network.
				</p>
			</div>

			<div className="ec-tool__section">
				<h3>Re-sync Team Role Across Network</h3>
				<p>
					Re-grants the team role on every subsite for every current
					team member. Useful after adding a new subsite to the
					network so existing team members get the role assignment
					on the new site. Idempotent.
				</p>
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
						'Re-sync Team Role Across Network'
					) }
				</Button>
				{ syncReport && (
					<div className="ec-tool__report">
						<p>{ syncReport.message }</p>
					</div>
				) }
			</div>

			<div className="ec-tool__section">
				<h3>User Management</h3>
				<SearchBox
					value={ search }
					onSearch={ ( term ) => {
						setSearch( term );
						setPage( 1 );
					} }
					placeholder="Search by username or email..."
				/>

				<DataTable
					columns={ columns }
					data={ users }
					isLoading={ isLoading }
					rowKey="ID"
					emptyMessage="No users found."
				/>

				<Pagination
					currentPage={ page }
					totalPages={ totalPages }
					totalItems={ totalUsers }
					onPageChange={ setPage }
				/>
			</div>
		</div>
	);
}
