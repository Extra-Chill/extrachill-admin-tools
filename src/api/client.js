/**
 * Admin Tools API Client
 *
 * Delegates all calls to @extrachill/api-client via WpApiFetchTransport.
 * Exports match the original function names so tool components need zero changes.
 */

import apiFetch from '@wordpress/api-fetch';
import { ExtraChillClient } from '@extrachill/api-client';
import { WpApiFetchTransport } from '@extrachill/api-client/wordpress';

const transport = new WpApiFetchTransport( apiFetch );
const client = new ExtraChillClient( transport );

const adminToolsApi = {
	getConfig: () => window.ecAdminToolsConfig || {},

	// Artist Access Requests
	getArtistAccessRequests: () => client.admin.listAccessRequests(),
	approveArtistAccess: ( userId, type ) =>
		client.admin.approveAccessRequest( userId, type ),
	rejectArtistAccess: ( userId ) =>
		client.admin.rejectAccessRequest( userId ),

	// Artist-User Relationships
	getArtistRelationships: ( view, search ) =>
		client.admin.listRelationships( view, search ),
	linkUserToArtist: ( userId, artistId ) =>
		client.admin.linkRelationship( userId, artistId ),
	unlinkUserFromArtist: ( userId, artistId ) =>
		client.admin.unlinkRelationship( userId, artistId ),
	getOrphanedRelationships: () => client.admin.findOrphanRelationships(),
	cleanupOrphan: ( userId, artistId ) =>
		client.admin.cleanupOrphan( userId, artistId ),

	// QR Code Generator
	generateQRCode: ( url ) => client.admin.generateQrCode( url ),

	// Lifetime Membership Management
	getLifetimeMemberships: ( search, page ) =>
		client.admin.listLifetimeMembers( search, page ),
	grantLifetimeMembership: ( userIdentifier ) =>
		client.admin.grantLifetimeMembership( userIdentifier ),
	revokeLifetimeMembership: ( userId ) =>
		client.admin.revokeLifetimeMembership( userId ),

	// Taxonomy Sync
	syncTaxonomies: ( taxonomies, targetSites ) =>
		client.admin.syncTaxonomies( taxonomies, targetSites ),

	// Team Member Management
	getTeamMembers: ( search, page ) =>
		client.admin.listTeamMembers( search, page ),
	syncTeamMembers: () => client.admin.syncTeamMembers(),
	updateTeamMemberOverride: ( userId, action ) =>
		client.admin.updateTeamMember( userId, action ),

	// User Search (for relationship management)
	searchUsers: ( term ) => client.users.search( term, 'admin' ),
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
