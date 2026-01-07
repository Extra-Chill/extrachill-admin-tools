/**
 * Lifetime Memberships Tool
 *
 * Manage lifetime memberships (grant/revoke) for users.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import {
	getLifetimeMemberships,
	grantLifetimeMembership,
	revokeLifetimeMembership,
} from '../api/client';
import { DataTable, SearchBox, Pagination } from '@extrachill/components';
import UserSearch from '../components/shared/UserSearch';

export default function LifetimeMemberships() {
	const { addNotice } = useAdminTools();
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isGranting, setIsGranting ] = useState( false );
	const [ members, setMembers ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalMembers, setTotalMembers ] = useState( 0 );

	const loadMembers = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await getLifetimeMemberships( search, page );
			setMembers( response.members || [] );
			setTotalPages( response.total_pages || 1 );
			setTotalMembers( response.total || 0 );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to load lifetime members.' );
		} finally {
			setIsLoading( false );
		}
	}, [ search, page, addNotice ] );

	useEffect( () => {
		loadMembers();
	}, [ loadMembers ] );

	const handleGrant = async ( user ) => {
		const confirmed = window.confirm(
			`Grant lifetime membership to ${ user.display_name } (${ user.username || user.user_login })?`
		);
		if ( ! confirmed ) {
			return;
		}

		setIsGranting( true );
		try {
			const response = await grantLifetimeMembership( user.username || user.user_login );
			addNotice( 'success', response.message || 'Membership granted successfully.' );
			loadMembers();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to grant membership.' );
		} finally {
			setIsGranting( false );
		}
	};

	const handleRevoke = async ( userId, username ) => {
		const confirmed = window.confirm(
			`Are you sure you want to revoke the lifetime membership for ${ username }?`
		);
		if ( ! confirmed ) {
			return;
		}

		try {
			const response = await revokeLifetimeMembership( userId );
			addNotice( 'success', response.message || 'Membership revoked successfully.' );
			loadMembers();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to revoke membership.' );
		}
	};

	const columns = [
		{ key: 'user_login', label: 'Username' },
		{ key: 'user_email', label: 'Email' },
		{ key: 'purchased', label: 'Member Since' },
		{ key: 'order_id', label: 'Order ID' },
		{
			key: 'actions',
			label: 'Actions',
			render: ( _, row ) => (
				<Button
					variant="secondary"
					isDestructive
					onClick={ () => handleRevoke( row.ID, row.user_login ) }
				>
					Revoke
				</Button>
			),
		},
	];

	return (
		<div className="ec-tool ec-tool--lifetime-memberships">
			<div className="ec-tool__description">
				<p>
					Manage Lifetime Extra Chill Memberships. Grant memberships to users
					manually or revoke existing ones. Lifetime members receive ad-free
					access across the entire network.
				</p>
			</div>

			<div className="ec-tool__section">
				<h3>Grant Lifetime Membership</h3>
				<UserSearch
					onSelect={ handleGrant }
					placeholder="Search for a user to grant membership..."
					label="Find a user by name, username, or email:"
				/>
				{ isGranting && (
					<div className="ec-tool__loading-overlay">
						<Spinner /> Granting Membership...
					</div>
				) }
			</div>

			<div className="ec-tool__section">
				<h3>Current Lifetime Members</h3>
				<SearchBox
					value={ search }
					onSearch={ ( term ) => {
						setSearch( term );
						setPage( 1 );
					} }
					placeholder="Search members..."
				/>

				<DataTable
					columns={ columns }
					data={ members }
					isLoading={ isLoading }
					rowKey="ID"
					emptyMessage="No lifetime members found."
				/>

				<Pagination
					currentPage={ page }
					totalPages={ totalPages }
					totalItems={ totalMembers }
					onPageChange={ setPage }
				/>
			</div>
		</div>
	);
}
