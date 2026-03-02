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

export const getConfig = () => window.ecAdminToolsConfig || {};

// Artist Access Requests
export const getArtistAccessRequests = () => client.admin.listAccessRequests();
export const approveArtistAccess = ( userId, type ) =>
	client.admin.approveAccessRequest( userId, type );
export const rejectArtistAccess = ( userId ) =>
	client.admin.rejectAccessRequest( userId );

// Artist-User Relationships
export const getArtistRelationships = ( view, search ) =>
	client.admin.listRelationships( view, search );
export const linkUserToArtist = ( userId, artistId ) =>
	client.admin.linkRelationship( userId, artistId );
export const unlinkUserFromArtist = ( userId, artistId ) =>
	client.admin.unlinkRelationship( userId, artistId );
export const getOrphanedRelationships = () =>
	client.admin.findOrphanRelationships();
export const cleanupOrphan = ( userId, artistId ) =>
	client.admin.cleanupOrphan( userId, artistId );

// QR Code Generator
export const generateQRCode = ( url ) => client.admin.generateQrCode( url );

// Lifetime Membership Management
export const getLifetimeMemberships = ( search, page ) =>
	client.admin.listLifetimeMembers( search, page );
export const grantLifetimeMembership = ( userIdentifier ) =>
	client.admin.grantLifetimeMembership( userIdentifier );
export const revokeLifetimeMembership = ( userId ) =>
	client.admin.revokeLifetimeMembership( userId );

// Taxonomy Sync
export const syncTaxonomies = ( taxonomies, targetSites ) =>
	client.admin.syncTaxonomies( taxonomies, targetSites );

// Team Member Management
export const getTeamMembers = ( search, page ) =>
	client.admin.listTeamMembers( search, page );
export const syncTeamMembers = () => client.admin.syncTeamMembers();
export const updateTeamMemberOverride = ( userId, action ) =>
	client.admin.updateTeamMember( userId, action );

// User Search (for relationship management)
export const searchUsers = ( term ) =>
	client.users.search( term, 'admin' );

export default {
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
};
