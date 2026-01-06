/**
 * Tag Migration Tool
 *
 * Migrate tags to custom taxonomies (festival, artist, venue).
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { useAdminTools } from '../context/AdminToolsContext';
import { getTags, migrateTags } from '../api/client';
import DataTable from '../components/shared/DataTable';
import SearchBox from '../components/shared/SearchBox';
import Pagination from '../components/shared/Pagination';

const TARGET_TAXONOMIES = [
	{ slug: 'festival', label: 'Festival' },
	{ slug: 'artist', label: 'Artist' },
	{ slug: 'venue', label: 'Venue' },
];

export default function TagMigration() {
	const { addNotice } = useAdminTools();
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isMigrating, setIsMigrating ] = useState( false );
	const [ tags, setTags ] = useState( [] );
	const [ selectedTagIds, setSelectedTagIds ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ totalTags, setTotalTags ] = useState( 0 );
	const [ report, setReport ] = useState( null );

	const loadTags = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await getTags( page, search );
			setTags( response.tags || [] );
			setTotalPages( response.total_pages || 1 );
			setTotalTags( response.total || 0 );
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to load tags.' );
		} finally {
			setIsLoading( false );
		}
	}, [ search, page, addNotice ] );

	useEffect( () => {
		loadTags();
	}, [ loadTags ] );

	const handleMigrate = async ( taxonomy ) => {
		if ( selectedTagIds.length === 0 ) {
			addNotice( 'error', 'Please select at least one tag.' );
			return;
		}

		const confirmed = window.confirm(
			`Migrate ${ selectedTagIds.length } tag(s) to ${ taxonomy }?`
		);
		if ( ! confirmed ) {
			return;
		}

		setIsMigrating( true );
		setReport( null );

		try {
			const response = await migrateTags( selectedTagIds, taxonomy );
			setReport( response.report || [] );
			addNotice( 'success', `Migrated ${ selectedTagIds.length } tag(s) to ${ taxonomy }.` );
			setSelectedTagIds( [] );
			loadTags();
		} catch ( error ) {
			addNotice( 'error', error.message || 'Failed to migrate tags.' );
		} finally {
			setIsMigrating( false );
		}
	};

	const columns = [
		{ key: 'name', label: 'Tag Name' },
		{ key: 'slug', label: 'Slug' },
		{ key: 'count', label: 'Count', width: '80px' },
	];

	return (
		<div className="ec-tool ec-tool--tag-migration">
			<div className="ec-tool__description">
				<p>
					Select tags and migrate them to the desired taxonomy. Tags will be
					removed from posts and deleted if unused.
				</p>
			</div>

			<SearchBox
				value={ search }
				onSearch={ ( term ) => {
					setSearch( term );
					setPage( 1 );
				} }
				placeholder="Search tags..."
			/>

			<DataTable
				columns={ columns }
				data={ tags }
				isLoading={ isLoading }
				selectable
				selectedIds={ selectedTagIds }
				onSelectChange={ setSelectedTagIds }
				rowKey="term_id"
				emptyMessage="No tags found."
			/>

			<Pagination
				currentPage={ page }
				totalPages={ totalPages }
				totalItems={ totalTags }
				onPageChange={ setPage }
			/>

			{ tags.length > 0 && (
				<div className="ec-tool__actions">
					<span>Migrate selected to:</span>
					{ TARGET_TAXONOMIES.map( ( tax ) => (
						<Button
							key={ tax.slug }
							variant="secondary"
							onClick={ () => handleMigrate( tax.slug ) }
							disabled={ isMigrating || selectedTagIds.length === 0 }
						>
							{ isMigrating ? <Spinner /> : tax.label }
						</Button>
					) ) }
				</div>
			) }

			{ report && report.length > 0 && (
				<div className="ec-tool__report">
					<h3>Migration Report</h3>
					<ul>
						{ report.map( ( line, index ) => (
							<li key={ index }>{ line }</li>
						) ) }
					</ul>
				</div>
			) }
		</div>
	);
}
