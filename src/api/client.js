/**
 * Admin Tools API Client
 *
 * Shared API functions using @wordpress/api-fetch.
 */

import apiFetch from '@wordpress/api-fetch';

const getConfig = () => window.ecAdminToolsConfig || {};

// Configure apiFetch middleware to include nonce from config
apiFetch.use( ( options, next ) => {
	const config = getConfig();
	if ( config.nonce && ! options.headers?.[ 'X-WP-Nonce' ] ) {
		options.headers = {
			...options.headers,
			'X-WP-Nonce': config.nonce,
		};
	}
	return next( options );
} );

export { getConfig };

const get = ( path ) => apiFetch( { path, method: 'GET' } );
const post = ( path, data ) => apiFetch( { path, method: 'POST', data } );
const put = ( path, data ) => apiFetch( { path, method: 'PUT', data } );
const del = ( path, data ) => apiFetch( { path, method: 'DELETE', data } );

// 404 Error Logger
export const get404LoggerSettings = () => get( 'extrachill/v1/admin/404-logger/settings' );
export const update404LoggerSettings = ( enabled ) =>
	post( 'extrachill/v1/admin/404-logger/settings', { enabled } );
export const get404LoggerStats = () => get( 'extrachill/v1/admin/404-logger/stats' );

// Artist Access Requests
export const getArtistAccessRequests = () => get( 'extrachill/v1/admin/artist-access' );
export const approveArtistAccess = ( userId, type ) =>
	post( `extrachill/v1/admin/artist-access/${ userId }/approve`, { type } );
export const rejectArtistAccess = ( userId ) =>
	post( `extrachill/v1/admin/artist-access/${ userId }/reject` );

// Artist-User Relationships
export const getArtistRelationships = ( view = 'artists', search = '' ) => {
	const params = new URLSearchParams( { view, search } );
	return get( `extrachill/v1/admin/artist-relationships?${ params }` );
};
export const linkUserToArtist = ( userId, artistId ) =>
	post( 'extrachill/v1/admin/artist-relationships/link', { user_id: userId, artist_id: artistId } );
export const unlinkUserFromArtist = ( userId, artistId ) =>
	post( 'extrachill/v1/admin/artist-relationships/unlink', { user_id: userId, artist_id: artistId } );
export const getOrphanedRelationships = () => get( 'extrachill/v1/admin/artist-relationships/orphans' );
export const cleanupOrphan = ( userId, artistId ) =>
	post( 'extrachill/v1/admin/artist-relationships/cleanup', { user_id: userId, artist_id: artistId } );

// Forum Topic Migration
export const getForums = () => get( 'extrachill/v1/admin/forum-topics/forums' );
export const getForumTopics = ( forumId = 0, search = '', page = 1 ) => {
	const params = new URLSearchParams( {
		forum_id: forumId,
		search,
		page,
	} );
	return get( `extrachill/v1/admin/forum-topics?${ params }` );
};
export const moveForumTopics = ( topicIds, destinationForumId ) =>
	post( 'extrachill/v1/admin/forum-topics/move', {
		topic_ids: topicIds,
		destination_forum_id: destinationForumId,
	} );

// QR Code Generator
export const generateQRCode = ( url ) =>
	post( 'extrachill/v1/tools/qr-code', { url } );

// Lifetime Membership Management
export const getLifetimeMemberships = ( search = '', page = 1 ) => {
	const params = new URLSearchParams( { search, page } );
	return get( `extrachill/v1/admin/lifetime-membership?${ params }` );
};
export const grantLifetimeMembership = ( userIdentifier ) =>
	post( 'extrachill/v1/admin/lifetime-membership/grant', { user_identifier: userIdentifier } );
export const revokeLifetimeMembership = ( userId ) =>
	del( `extrachill/v1/admin/lifetime-membership/${ userId }` );

// Tag Migration
export const getTags = ( page = 1, search = '' ) => {
	const params = new URLSearchParams( { page, search } );
	return get( `extrachill/v1/admin/tags?${ params }` );
};
export const migrateTags = ( tagIds, taxonomy ) =>
	post( 'extrachill/v1/admin/tags/migrate', { tag_ids: tagIds, taxonomy } );

// Taxonomy Sync
export const syncTaxonomies = ( taxonomies, targetSites ) =>
	post( 'extrachill/v1/admin/taxonomies/sync', { taxonomies, target_sites: targetSites } );

// Team Member Management
export const getTeamMembers = ( search = '', page = 1 ) => {
	const params = new URLSearchParams( { search, page } );
	return get( `extrachill/v1/admin/team-members?${ params }` );
};
export const syncTeamMembers = () => post( 'extrachill/v1/admin/team-members/sync' );
export const updateTeamMemberOverride = ( userId, action ) =>
	post( `extrachill/v1/admin/team-members/${ userId }`, { action } );

// User Search (for relationship management)
export const searchUsers = ( term ) => {
	const params = new URLSearchParams( { term, context: 'admin' } );
	return get( `extrachill/v1/users/search?${ params }` );
};

export default {
	getConfig,
	get404LoggerSettings,
	update404LoggerSettings,
	get404LoggerStats,
	getArtistAccessRequests,
	approveArtistAccess,
	rejectArtistAccess,
	getArtistRelationships,
	linkUserToArtist,
	unlinkUserFromArtist,
	getOrphanedRelationships,
	cleanupOrphan,
	getForums,
	getForumTopics,
	moveForumTopics,
	generateQRCode,
	getTags,
	migrateTags,
	syncTaxonomies,
	getLifetimeMemberships,
	grantLifetimeMembership,
	revokeLifetimeMembership,
	getTeamMembers,
	syncTeamMembers,
	updateTeamMemberOverride,
	searchUsers,
};
