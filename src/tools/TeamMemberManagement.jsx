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
			key: 'source',
			label: 'Source',
			render: ( value ) => <span className="ec-source">{ value }</span>,
		},
		{
			key: 'actions',
			label: 'Actions',
			render: ( _, row ) => (
				<SelectControl
					value=""
					options={ [
						{ label: '-- Select Action --', value: '' },
						{ label: 'Force Add', value: 'force_add' },
						{ label: 'Force Remove', value: 'force_remove' },
						{ label: 'Reset to Auto', value: 'reset_auto' },
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
					Sync team members from the main site (extrachill.com) and manage
					manual overrides for fired staff or community moderators.
				</p>
			</div>

			<div className="ec-tool__section">
				<h3>Sync Team Members</h3>
				<p>
					Automatically set team member status for all users with
					extrachill.com accounts. Manual overrides will be preserved.
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
						'Sync Team Members from Main Site'
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
