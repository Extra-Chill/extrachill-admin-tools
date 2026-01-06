# Taxonomy Sync Tool

The Taxonomy Sync tool allows administrators to synchronize taxonomy terms from the main site (extrachill.com) to other sites across the multisite network.

## Overview

In a multisite network, taxonomies like "Artist", "Festival", "Location", and "Venue" are often shared. This tool ensures that terms created on the main site are consistently replicated across the network, maintaining data integrity and enabling cross-site content relationships.

## React Interface

The tool is implemented as a React component (`TaxonomySync.jsx`) within the Admin Tools SPA.

### Features
- **Target Site Selection**: Choose one or more sites to receive the sync.
- **Taxonomy Selection**: Select specific taxonomies to synchronize (Location, Festival, Artist, Venue).
- **Hierarchical Support**: Maintains parent-child relationships for hierarchical taxonomies like Location.
- **Real-time Reporting**: Displays a JSON report of the sync operation, including created, skipped, and failed terms.

## API Integration

The React component interacts with the `extrachill-api` plugin via the following endpoint:

`POST /wp-json/extrachill/v1/admin/taxonomies/sync`

### Parameters
- `taxonomies` (array): List of taxonomy slugs to sync.
- `site_ids` (array): List of target blog IDs.

## Database Operations

The tool uses standard WordPress functions (`get_terms`, `wp_insert_term`) within `switch_to_blog()` contexts to ensure terms are correctly created on target sites with matching slugs and metadata.
