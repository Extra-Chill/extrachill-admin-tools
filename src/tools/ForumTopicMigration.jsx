/**
 * Forum Topic Migration Tool
 *
 * Bulk move topics between forums.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, SelectControl, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import { getForums, getForumTopics, moveForumTopics } from '../api/client';
import DataTable from '../components/shared/DataTable';
import SearchBox from '../components/shared/SearchBox';
import Pagination from '../components/shared/Pagination';

export default function ForumTopicMigration() {
	const { addNotice } = useAdminTools();
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isMoving, setIsMoving ] = useState( false );
	const [ forums, setForums ] = useState( [] );
	const [ topics, setTopics ] = useState( [] );
	const [ selectedTopicIds, setSelectedTopicIds ] = useState( [] );
	const [ sourceForumId, setSourceForumId ] = useState( 0 );
	const [ destinationForumId, setDestinationForumId ] = useState( '' );
	const [ search, setSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalTopics, setTotalTopics ] = useState( 0 );
	const [ report, setReport ] = useState( null );

	// Load forums on mount
	useEffect( () => {
		const loadForums = async () => {
			try {
				const response = await getForums();
				setForums( response.forums || [] );
			} catch ( error ) {
				addNotice( 'error', error.message || 'Failed to load forums.' );
			}
		};
		loadForums();
	}, [ addNotice ] );

	// Load topics when filters change
	const loadTopics = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await getForumTopics( sourceForumId, search, page );
			setTopics( response.topics || [] );
			setTotalPages( response.pages || 1 );
			setTotalTopics( response.total || 0 );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to load topics.' );
		} finally {
			setIsLoading( false );
		}
	}, [ sourceForumId, search, page, addNotice ] );

	useEffect( () => {
		loadTopics();
	}, [ loadTopics ] );

	const handleMove = async () => {
		if ( selectedTopicIds.length === 0 ) {
			addNotice( 'error', 'Please select at least one topic.' );
			return;
		}
		if ( ! destinationForumId ) {
			addNotice( 'error', 'Please select a destination forum.' );
			return;
		}

		const confirmed = window.confirm(
			`Move ${ selectedTopicIds.length } topic(s) to the selected forum?`
		);
		if ( ! confirmed ) {
			return;
		}

		setIsMoving( true );
		setReport( null );

		try {
			const response = await moveForumTopics(
				selectedTopicIds,
				parseInt( destinationForumId, 10 )
			);
			setReport( response );
			addNotice( 'success', response.message );
			setSelectedTopicIds( [] );
			loadTopics();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to move topics.' );
		} finally {
			setIsMoving( false );
		}
	};

	const forumOptions = [
		{ label: 'All Forums', value: 0 },
		...forums.map( ( forum ) => ( {
			label: `${ '\u2014'.repeat( forum.depth || 0 ) } ${ forum.title } (${ forum.topic_count } topics)`,
			value: forum.id,
		} ) ),
	];

	const destinationOptions = [
		{ label: '-- Select Destination --', value: '' },
		...forums.map( ( forum ) => ( {
			label: `${ '\u2014'.repeat( forum.depth || 0 ) } ${ forum.title }`,
			value: forum.id,
		} ) ),
	];

	const columns = [
		{
			key: 'title',
			label: 'Topic Title',
			render: ( value, row ) => (
				<a href={ row.url } target="_blank" rel="noopener noreferrer">
					{ value }
				</a>
			),
		},
		{ key: 'forum_title', label: 'Current Forum' },
		{ key: 'author_name', label: 'Author' },
		{ key: 'reply_count', label: 'Replies', width: '80px' },
		{
			key: 'date',
			label: 'Date',
			width: '100px',
			render: ( value ) => {
				const date = new Date( value );
				return date.toLocaleDateString();
			},
		},
	];

	// Calculate total topics across all forums
	const totalForumTopics = forums.reduce(
		( sum, forum ) => sum + ( forum.topic_count || 0 ),
		0
	);

	return (
		<div className="ec-tool ec-tool--forum-migration">
			<div className="ec-tool__description">
				<p>
					Move topics from one forum to another. Select a source forum to
					filter, then choose topics and a destination.
				</p>
				<p>
					<strong>Total:</strong> { totalForumTopics } topics across{ ' ' }
					{ forums.length } forums
				</p>
			</div>

			<div className="ec-tool__filters">
				<SelectControl
					label="Source Forum"
					value={ sourceForumId }
					options={ forumOptions }
					onChange={ ( value ) => {
						setSourceForumId( parseInt( value, 10 ) );
						setPage( 1 );
						setSelectedTopicIds( [] );
					} }
					__nextHasNoMarginBottom
				/>

				<SearchBox
					value={ search }
					onSearch={ ( term ) => {
						setSearch( term );
						setPage( 1 );
					} }
					placeholder="Search topic titles..."
				/>
			</div>

			<div className="ec-tool__bulk-actions">
				<SelectControl
					label="Destination Forum"
					value={ destinationForumId }
					options={ destinationOptions }
					onChange={ setDestinationForumId }
					__nextHasNoMarginBottom
				/>
				<Button
					variant="primary"
					onClick={ handleMove }
					disabled={
						isMoving ||
						selectedTopicIds.length === 0 ||
						! destinationForumId
					}
				>
					{ isMoving ? (
						<>
							<Spinner /> Moving...
						</>
					) : (
						`Move Selected (${ selectedTopicIds.length })`
					) }
				</Button>
			</div>

			<DataTable
				columns={ columns }
				data={ topics }
				isLoading={ isLoading }
				selectable
				selectedIds={ selectedTopicIds }
				onSelectChange={ setSelectedTopicIds }
				rowKey="id"
				emptyMessage="No topics found."
			/>

			<Pagination
				currentPage={ page }
				totalPages={ totalPages }
				totalItems={ totalTopics }
				onPageChange={ setPage }
			/>

			{ report && (
				<div className="ec-tool__report">
					<h3>Migration Result</h3>
					<p>{ report.message }</p>
					{ report.moved && report.moved.length > 0 && (
						<>
							<h4>Moved Topics:</h4>
							<ul>
								{ report.moved.map( ( item ) => (
									<li key={ item.topic_id }>
										{ item.title } ({ item.reply_count } replies)
									</li>
								) ) }
							</ul>
						</>
					) }
					{ report.failed && report.failed.length > 0 && (
						<>
							<h4>Failed:</h4>
							<ul>
								{ report.failed.map( ( item ) => (
									<li key={ item.id }>
										{ item.title }: { item.error }
									</li>
								) ) }
							</ul>
						</>
					) }
				</div>
			) }
		</div>
	);
}
