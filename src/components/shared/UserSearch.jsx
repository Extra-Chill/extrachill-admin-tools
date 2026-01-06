/**
 * UserSearch Component
 *
 * Reusable user search with autocomplete results.
 */

import { useState } from '@wordpress/element';
import { TextControl, Button, Spinner } from '@wordpress/components';
import { searchUsers } from '../../api/client';

export default function UserSearch( {
	onSelect,
	placeholder = 'Search by name, username, or email...',
	label = 'Search for a user:',
} ) {
	const [ term, setTerm ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ isSearching, setIsSearching ] = useState( false );

	const handleSearch = async () => {
		if ( ! term || term.length < 2 ) {
			return;
		}
		setIsSearching( true );
		try {
			const response = await searchUsers( term );
			// Handle both { users: [] } and raw [] responses if API varies
			const users = Array.isArray( response ) ? response : response.users || [];
			setResults( users );
		} catch ( error ) {
			// Errors should be handled by the parent context/notices
			console.error( 'User search failed:', error );
		} finally {
			setIsSearching( false );
		}
	};

	const handleSelect = ( user ) => {
		onSelect( user );
		setTerm( '' );
		setResults( [] );
	};

	const handleKeyDown = ( e ) => {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			handleSearch();
		}
	};

	return (
		<div className="ec-user-search-component">
			{ label && <p className="ec-user-search-label">{ label }</p> }
			<div className="ec-user-search-input-group">
				<TextControl
					value={ term }
					onChange={ setTerm }
					placeholder={ placeholder }
					onKeyDown={ handleKeyDown }
					__nextHasNoMarginBottom
				/>
				<Button
					variant="secondary"
					onClick={ handleSearch }
					disabled={ isSearching }
				>
					{ isSearching ? <Spinner /> : 'Search' }
				</Button>
			</div>

			{ results.length > 0 && (
				<ul className="ec-user-search-results">
					{ results.map( ( user ) => (
						<li key={ user.id || user.ID }>
							<div className="ec-user-search-result-info">
								{ user.avatar_url && (
									<img
										src={ user.avatar_url }
										alt=""
										className="ec-user-search-avatar"
									/>
								) }
								<span className="ec-user-search-name">
									{ user.display_name } ({ user.username || user.user_login })
								</span>
							</div>
							<Button
								variant="primary"
								onClick={ () => handleSelect( user ) }
							>
								Select
							</Button>
						</li>
					) ) }
				</ul>
			) }

			{ ! isSearching && term.length >= 2 && results.length === 0 && (
				<p className="ec-user-search-no-results">No users found.</p>
			) }
		</div>
	);
}
