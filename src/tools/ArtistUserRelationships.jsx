/**
 * Artist-User Relationships Tool
 *
 * Manage bidirectional relationships between users and artist profiles.
 * Three views: Artists, Users, Orphans.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, TabPanel, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import {
	getArtistRelationships,
	linkUserToArtist,
	unlinkUserFromArtist,
	getOrphanedRelationships,
	cleanupOrphan,
} from '../api/client';
import { DataTable, SearchBox, Modal } from '@extrachill/components';
import UserSearch from '../components/shared/UserSearch';

export default function ArtistUserRelationships() {
	const { addNotice } = useAdminTools();
	const [ activeView, setActiveView ] = useState( 'artists' );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ data, setData ] = useState( [] );
	const [ orphans, setOrphans ] = useState( [] );
	const [ search, setSearch ] = useState( '' );

	// Modal state for adding user to artist
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ selectedArtistId, setSelectedArtistId ] = useState( null );

	const loadData = useCallback( async () => {
		setIsLoading( true );
		try {
			if ( activeView === 'orphans' ) {
				const response = await getOrphanedRelationships();
				setOrphans( response.orphans || [] );
			} else {
				const response = await getArtistRelationships( activeView, search );
				setData( response.items || [] );
			}
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to load data.' );
		} finally {
			setIsLoading( false );
		}
	}, [ activeView, search, addNotice ] );

	useEffect( () => {
		loadData();
	}, [ loadData ] );

	const handleUnlink = async ( userId, artistId ) => {
		try {
			await unlinkUserFromArtist( userId, artistId );
			addNotice( 'success', 'User unlinked from artist.' );
			loadData();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to unlink user.' );
		}
	};

	const handleCleanup = async ( userId, artistId ) => {
		try {
			await cleanupOrphan( userId, artistId );
			addNotice( 'success', 'Orphaned relationship cleaned up.' );
			loadData();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to cleanup.' );
		}
	};

	const openAddUserModal = ( artistId ) => {
		setSelectedArtistId( artistId );
		setIsModalOpen( true );
	};

	const handleLinkUser = async ( userId ) => {
		try {
			await linkUserToArtist( userId, selectedArtistId );
			addNotice( 'success', 'User linked to artist.' );
			setIsModalOpen( false );
			loadData();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to link user.' );
		}
	};

	const artistColumns = [
		{
			key: 'title',
			label: 'Artist Profile',
			render: ( value, row ) => (
				<>
					<strong>{ value }</strong>
					<br />
					<small>ID: { row.id }</small>
				</>
			),
		},
		{
			key: 'members',
			label: 'Members',
			render: ( members, row ) => (
				<>
					<span className="ec-member-count">{ members.length } members</span>
					{ members.length > 0 && (
						<ul className="ec-member-list">
							{ members.map( ( member ) => (
								<li key={ member.ID }>
									{ member.display_name } ({ member.user_login })
									<Button
										variant="link"
										isDestructive
										onClick={ () => handleUnlink( member.ID, row.id ) }
									>
										Remove
									</Button>
								</li>
							) ) }
						</ul>
					) }
				</>
			),
		},
		{
			key: 'actions',
			label: 'Actions',
			render: ( _, row ) => (
				<Button
					variant="secondary"
					onClick={ () => openAddUserModal( row.id ) }
				>
					Add User
				</Button>
			),
		},
	];

	const userColumns = [
		{
			key: 'display_name',
			label: 'User',
			render: ( value, row ) => (
				<>
					<strong>{ value }</strong>
					<br />
					<small>{ row.user_login } ({ row.user_email })</small>
				</>
			),
		},
		{
			key: 'artists',
			label: 'Artist Profiles',
			render: ( artists, row ) =>
				artists.length > 0 ? (
					<ul className="ec-member-list">
						{ artists.map( ( artist ) => (
							<li key={ artist.ID }>
								{ artist.post_title }
								<Button
									variant="link"
									isDestructive
									onClick={ () => handleUnlink( row.ID, artist.ID ) }
								>
									Remove
								</Button>
							</li>
						) ) }
					</ul>
				) : (
					<span style={ { color: '#646970' } }>No artist profiles</span>
				),
		},
	];

	const orphanColumns = [
		{
			key: 'user',
			label: 'User',
			render: ( user ) => `${ user.display_name } (${ user.user_login })`,
		},
		{ key: 'invalid_artist_id', label: 'Invalid Artist ID' },
		{
			key: 'actions',
			label: 'Action',
			render: ( _, row ) => (
				<Button
					variant="secondary"
					onClick={ () => handleCleanup( row.user.ID, row.invalid_artist_id ) }
				>
					Clean Up
				</Button>
			),
		},
	];

	const tabs = [
		{
			name: 'artists',
			title: 'Artists',
			className: 'ec-tab',
		},
		{
			name: 'users',
			title: 'Users',
			className: 'ec-tab',
		},
		{
			name: 'orphans',
			title: 'Orphans',
			className: 'ec-tab',
		},
	];

	return (
		<div className="ec-tool ec-tool--relationships">
			<div className="ec-tool__description">
				<p>
					Manage relationships between users and artist profiles. Link users
					to artists, view all relationships, and detect orphaned data.
				</p>
			</div>

			<TabPanel
				tabs={ tabs }
				onSelect={ ( tabName ) => {
					setActiveView( tabName );
					setSearch( '' );
					setData( [] );
				} }
			>
				{ ( tab ) => (
					<div className="ec-tool__tab-content">
						{ tab.name !== 'orphans' && (
							<SearchBox
								value={ search }
								onSearch={ setSearch }
								placeholder="Search..."
							/>
						) }

						{ tab.name === 'artists' && (
							<DataTable
								columns={ artistColumns }
								data={ data }
								isLoading={ isLoading }
								rowKey="id"
								emptyMessage="No artist profiles found."
							/>
						) }

						{ tab.name === 'users' && (
							<DataTable
								columns={ userColumns }
								data={ data }
								isLoading={ isLoading }
								rowKey="ID"
								emptyMessage="No users found."
							/>
						) }

						{ tab.name === 'orphans' && (
							<>
								<h3>Orphaned Relationships</h3>
								<p>
									Users with artist profile IDs that no longer exist, or
									artist profiles with invalid user IDs.
								</p>
								{ isLoading ? (
									<Spinner />
								) : orphans.length === 0 ? (
									<div className="ec-tool__success">
										All data is clean! No orphaned relationships found.
									</div>
								) : (
									<DataTable
										columns={ orphanColumns }
										data={ orphans }
										isLoading={ false }
										rowKey="invalid_artist_id"
										emptyMessage="No orphans found."
									/>
								) }
							</>
						) }
					</div>
				) }
			</TabPanel>

			<Modal
				title="Add User to Artist"
				isOpen={ isModalOpen }
				onClose={ () => setIsModalOpen( false ) }
			>
				<UserSearch
					onSelect={ ( user ) => handleLinkUser( user.ID || user.id ) }
					label="Search for a user by name, username, or email:"
				/>
			</Modal>
		</div>
	);
}
