/**
 * Artist Access Requests Tool
 *
 * Review and approve/reject artist platform access requests.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import {
	getArtistAccessRequests,
	approveArtistAccess,
	rejectArtistAccess,
} from '../api/client';
import { DataTable } from '@extrachill/components';

export default function ArtistAccessRequests() {
	const { addNotice } = useAdminTools();
	const [ isLoading, setIsLoading ] = useState( true );
	const [ requests, setRequests ] = useState( [] );
	const [ processingId, setProcessingId ] = useState( null );

	const loadRequests = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await getArtistAccessRequests();
			setRequests( response.requests || [] );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to load requests.' );
		} finally {
			setIsLoading( false );
		}
	}, [ addNotice ] );

	useEffect( () => {
		loadRequests();
	}, [ loadRequests ] );

	const handleApprove = async ( userId, type ) => {
		setProcessingId( userId );
		try {
			await approveArtistAccess( userId, type );
			addNotice( 'success', 'Access request approved.' );
			loadRequests();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to approve request.' );
		} finally {
			setProcessingId( null );
		}
	};

	const handleReject = async ( userId ) => {
		setProcessingId( userId );
		try {
			await rejectArtistAccess( userId );
			addNotice( 'success', 'Access request rejected.' );
			loadRequests();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to reject request.' );
		} finally {
			setProcessingId( null );
		}
	};

	const columns = [
		{ key: 'user_login', label: 'Username' },
		{ key: 'user_email', label: 'Email' },
		{
			key: 'type',
			label: 'Request Type',
			render: ( value ) => (
				<span className="ec-badge ec-badge--info">
					{ value === 'artist' ? 'Musician' : 'Industry Professional' }
				</span>
			),
		},
		{
			key: 'requested_at',
			label: 'Requested',
			render: ( value ) => {
				if ( ! value ) return 'Unknown';
				const date = new Date( value * 1000 );
				return date.toLocaleDateString();
			},
		},
		{
			key: 'actions',
			label: 'Actions',
			render: ( _, row ) => (
				<div className="ec-tool__row-actions">
					<Button
						variant="primary"
						onClick={ () => handleApprove( row.user_id, row.type ) }
						disabled={ processingId === row.user_id }
					>
						{ processingId === row.user_id ? <Spinner /> : 'Approve' }
					</Button>
					<Button
						variant="secondary"
						isDestructive
						onClick={ () => handleReject( row.user_id ) }
						disabled={ processingId === row.user_id }
					>
						Reject
					</Button>
				</div>
			),
		},
	];

	return (
		<div className="ec-tool ec-tool--artist-access">
			<div className="ec-tool__description">
				<p>Review and approve artist platform access requests from users.</p>
			</div>

			<DataTable
				columns={ columns }
				data={ requests }
				isLoading={ isLoading }
				rowKey="user_id"
				emptyMessage="No pending artist access requests."
			/>
		</div>
	);
}
