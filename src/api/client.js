/**
 * Admin Tools API Client
 *
 * Migrated to wp-native-client (Abilities API) where abilities exist.
 * Calls without ability equivalents fall back to direct apiFetch with
 * REST paths — see TODO comments for follow-up ability registration.
 *
 * Part of M8 umbrella retirement (extrachill-app#38).
 */

import apiFetch from '@wordpress/api-fetch';
import { WPNativeClient } from 'wp-native-client';
import { WpApiFetchTransport } from 'wp-native-client/wordpress';

const transport = new WpApiFetchTransport( apiFetch );
const client = new WPNativeClient( transport );

// ── Helpers for fallback REST calls (no ability equivalent yet) ─────────

/**
 * Build a query string from an object of params.
 * Skips undefined values.
 */
function buildQuery( params ) {
	const entries = Object.entries( params ).filter(
		( [ , v ] ) => v !== undefined
	);
	if ( entries.length === 0 ) {
		return '';
	}
	const search = new URLSearchParams();
	for ( const [ key, value ] of entries ) {
		search.set( key, String( value ) );
	}
	return '?' + search.toString();
}

// ── Admin Tools API ─────────────────────────────────────────────────────

const adminToolsApi = {
	getConfig: () => window.ecAdminToolsConfig || {},

	// ── Artist Access Requests (migrated → abilities) ───────────────────
	getArtistAccessRequests: () =>
		client.execute( 'extrachill/list-artist-access-requests' ),
	approveArtistAccess: ( userId, type ) =>
		client.execute( 'extrachill/approve-artist-access', {
			user_id: userId,
			type,
		} ),
	rejectArtistAccess: ( userId ) =>
		client.execute( 'extrachill/reject-artist-access', {
			user_id: userId,
		} ),

	// ── Artist-User Relationships ───────────────────────────────────────
	// TODO: M8 follow-up — register abilities for artist-relationship
	//       admin endpoints, then migrate these calls.
	//       REST routes: extrachill/v1/admin/artist-relationships/*
	getArtistRelationships: ( view, search ) =>
		apiFetch( {
			path: `extrachill/v1/admin/artist-relationships${ buildQuery( {
				view,
				search,
			} ) }`,
		} ),
	linkUserToArtist: ( userId, artistId ) =>
		apiFetch( {
			path: 'extrachill/v1/admin/artist-relationships/link',
			method: 'POST',
			data: { user_id: userId, artist_id: artistId },
		} ),
	unlinkUserFromArtist: ( userId, artistId ) =>
		apiFetch( {
			path: 'extrachill/v1/admin/artist-relationships/unlink',
			method: 'POST',
			data: { user_id: userId, artist_id: artistId },
		} ),
	getOrphanedRelationships: () =>
		apiFetch( {
			path: 'extrachill/v1/admin/artist-relationships/orphans',
		} ),
	cleanupOrphan: ( userId, artistId ) =>
		apiFetch( {
			path: 'extrachill/v1/admin/artist-relationships/cleanup',
			method: 'POST',
			data: { user_id: userId, artist_id: artistId },
		} ),

	// ── QR Code Generator ───────────────────────────────────────────────
	// TODO: M8 follow-up — register ability for QR code generation.
	//       REST route: extrachill/v1/tools/qr-code
	generateQRCode: ( url ) =>
		apiFetch( {
			path: 'extrachill/v1/tools/qr-code',
			method: 'POST',
			data: { url },
		} ),

	// ── Lifetime Membership Management (migrated → abilities) ───────────
	// TODO: M8 follow-up — register ability for listing lifetime members.
	//       REST route: extrachill/v1/admin/lifetime-membership
	getLifetimeMemberships: ( search, page ) =>
		apiFetch( {
			path: `extrachill/v1/admin/lifetime-membership${ buildQuery( {
				search,
				page,
			} ) }`,
		} ),
	grantLifetimeMembership: ( userIdentifier ) =>
		client.execute( 'extrachill/grant-lifetime-membership', {
			user_identifier: userIdentifier,
		} ),
	revokeLifetimeMembership: ( userId ) =>
		client.execute( 'extrachill/revoke-lifetime-membership', {
			user_id: userId,
		} ),

	// ── Taxonomy Sync (migrated → ability) ──────────────────────────────
	syncTaxonomies: ( taxonomies, targetSites ) =>
		client.execute( 'extrachill/sync-taxonomies', {
			taxonomies,
			target_sites: targetSites,
		} ),

	// ── Team Role Management ────────────────────────────────────────────
	// The extra_chill_team WP role is the source of truth; these
	// endpoints grant/revoke it network-wide.
	// TODO: M8 follow-up — register ability for listing team members.
	//       REST route: extrachill/v1/admin/team-members
	getTeamMembers: ( search, page ) =>
		apiFetch( {
			path: `extrachill/v1/admin/team-members${ buildQuery( {
				search,
				page,
			} ) }`,
		} ),
	syncTeamMembers: () =>
		client.execute( 'extrachill/sync-team-members' ),
	updateTeamMemberOverride: ( userId, action ) =>
		client.execute( 'extrachill/manage-team-member', {
			user_id: userId,
			action,
		} ),

	// ── User Search ─────────────────────────────────────────────────────
	// TODO: M8 follow-up — register ability for user search.
	//       REST route: extrachill/v1/users/search
	searchUsers: ( term ) =>
		apiFetch( {
			path: `extrachill/v1/users/search${ buildQuery( {
				term,
				context: 'admin',
			} ) }`,
		} ),
};

export const {
	getConfig,
	getArtistAccessRequests,
	approveArtistAccess,
	rejectArtistAccess,
	getArtistRelationships,
	linkUserToArtist,
	unlinkUserFromArtist,
	getOrphanedRelationships,
	cleanupOrphan,
	generateQRCode,
	syncTaxonomies,
	getLifetimeMemberships,
	grantLifetimeMembership,
	revokeLifetimeMembership,
	getTeamMembers,
	syncTeamMembers,
	updateTeamMemberOverride,
	searchUsers,
} = adminToolsApi;

export default adminToolsApi;
