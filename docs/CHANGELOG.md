 # Changelog

 All notable changes to this project will be documented in this file.

 The format is based on [Keep a Changelog](https://keepachangelog.com/en/1/0/0/),
 and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

 ## [2.0.1] - 2026-01-07

### Changed
- **Shared UI Components**: Switched tool tables/pagination/search/modal UI to `@extrachill/components` (replacing local `src/components/shared/*` imports).
- **User Search Feedback**: Updated `UserSearch` to only show "No users found" after an actual search attempt (tracks `hasSearched` state).
- **Artist-User Relationships UI**: Clear loaded relationship data when switching between views to avoid stale results.
- **Build Packaging**: Updated `build.sh` symlink target and adjusted `.buildignore` to allow `/build` output directory in the repo.

## [2.0.0] - 2026-01-05

### Added
- **React-Based Tooling System**: Completely overhauled the administrative interface to a modern React-based architecture.
  - Centralized `AdminTools` app component with client-side routing.
  - Reusable UI component library (`DataTable`, `UserSearch`, `Modal`, `Pagination`).
  - Standardized REST API client for all tool interactions.
- **Modernized Tools**:
  - `ArtistUserRelationships.jsx`: New React interface for managing user-artist links with real-time searching.
  - `LifetimeMemberships.jsx`: Enhanced membership management with better user feedback.
  - `TeamMemberManagement.jsx`: Refactored team sync with granular control.
  - `ArtistAccessRequests.jsx`: Streamlined approval/rejection workflow.
  - `TagMigration.jsx`: Improved bulk migration interface with progress tracking.
  - `ErrorLogger.jsx`: New visualization for 404 error tracking.
- **Enhanced Permissions**: All tools now consistently verify `manage_network_options` and utilize REST-based authentication.

### Changed
- **Major Version Bump**: Shift to version 2.0.0 reflecting the complete architectural migration from PHP/AJAX to React/REST.
- **Asset Loading**: Consolidated legacy individual scripts and styles into a single compiled React bundle.
- **Network Admin Integration**: Centralized the administrative interface within the Network Admin dashboard.
- **Decommissioned legacy PHP/AJAX handlers**: Replaced with unified REST endpoints and React components. Removed legacy `assets/` and `inc/tools/` files.

 ## [1.3.0] - 2026-01-03

### Added
- **Network Admin Integration**: Migrated the primary interface from Tools menu to the Network Admin for centralized management across the multisite network.
- **Site Selector Context**: Added a site selector to the admin interface, allowing tools to be executed within the context of any site in the network using `switch_to_blog()`.
- **Lifetime Membership Management**: New tool for managing Lifetime Extra Chill Memberships (rebranding and upgrading the Ad-Free License system).

### Changed
- **Permissions**: Updated all admin access requirements from `manage_options` to `manage_network_options`.
- **Tool Updates**: Updated Taxonomy Sync and Artist platform repair tools to support the new site-switching architecture.
- **REST API Enhancements**: Updated taxonomy sync and membership management endpoints to support target site selection parameters.

### Fixed
- **Admin Assets**: Corrected asset loading hooks for compatibility with the network admin dashboard.

## [1.2.9] - 2025-12-23

 ### Removed
 - **Festival Wire Migration Tool**: Removed after migration completion

 ## [1.2.8] - 2025-12-23

 ### Added
 - **Festival Wire Migration**: New "Delete Source Posts" operation with batched deletion and progress tracking, allowing safe removal of source posts after successful migration
 - **Safety Check**: Added verification preventing source deletion unless target blog has migrated posts

 ### Changed
 - **Performance**: Optimized duplicate detection during migration by using in-memory title lookup instead of per-post database queries
 - **Refactoring**: Moved migration counters from global scope to function scope in JavaScript for better encapsulation
 - **Clarity**: Updated button labels ("Reset Target") and confirmation messages for more precise operation descriptions

 ### Documentation
 - **Artist-User Relationships**: Updated documentation to reflect bidirectional storage with `_artist_member_ids` and `_artist_profile_ids` meta keys
 - **API Documentation**: Migrated examples from AJAX to REST API endpoints throughout
 - **Data Structure**: Corrected all code examples and queries to use current meta key conventions

 ## [1.2.7] - 2025-12-22

 ### Changed
 - **Festival Wire Migration Tool**: Refactored from REST API to self-contained AJAX implementation for improved reliability and reduced external dependencies
   - Changed from manual batch processing to continuous automated migration with real-time progress tracking
   - Added visual progress bar with live statistics (migrated/skipped counts)
   - Changed reset operation to delete all target posts on wire.extrachill.com instead of source posts
   - Added duplicate detection to skip posts with matching titles on target
   - Added featured image copying from source to target posts during migration
   - Improved error handling with proper blog switching and try/finally blocks
   - Fixed batch size to constant value (50) for consistent processing

 ## [1.2.6] - 2025-12-22

### Added
- **Festival Wire Migration Tool**: New conditional admin tool for migrating festival_wire posts from extrachill.com to wire.extrachill.com. Includes preflight checks, batch migration with attachments, validation, and source cleanup operations. Requires extrachill-news-wire plugin.

### Changed
- **Taxonomy Sync Modernization**: Converted taxonomy sync tool from jQuery AJAX to vanilla JavaScript fetch API, migrating from admin-ajax.php to REST API endpoint (`/admin/taxonomies/sync`). Simplified server-side code by removing direct database operations in favor of centralized REST API handling.

### Technical
- **Code Architecture**: Improved consistency across admin tools with uniform REST API usage and vanilla JavaScript implementations.
- **Performance**: Enhanced taxonomy sync performance through REST API centralization and reduced client-side dependencies.

## [1.2.3] - 2025-12-13

### Added
- **Link Page Font Migration Tool**: One-time migration tool to normalize link page font values from legacy "WilcoLoftSans" to theme-aligned "Loft Sans" for title and body font-family CSS variables

### Removed
- **Scaled Image URL Fix Tool**: Removed completed one-time migration tool
- **Block Namespace Migration Tool**: Removed completed one-time migration tool

### Technical
- **Code Cleanup**: Removed obsolete migration tools that had completed their purpose

## [1.2.5] - 2025-12-18

### Added
- **REST API Migration**: Migrated artist access requests from admin-ajax.php to REST API endpoints (`/admin/artist-access/approve` and `/admin/artist-access/reject`) for improved architecture and consistency
- **Comprehensive Documentation**: Added extensive API patterns and database schemas documentation in `docs/api-patterns.md`
- **Tool Documentation Suite**: Created detailed documentation for core tools in `docs/tools/` directory:
  - `404-error-logger.md` - Complete 404 logging system documentation with database schema, queries, and troubleshooting
  - `artist-user-relationships.md` - Full guide to artist-user relationship management, database operations, and common issues
  - `tag-migration.md` - Detailed tag migration tool documentation with process flow and best practices
- **README Enhancement**: Added comprehensive documentation section linking to all new tool and API documentation

### Changed
- **JavaScript Modernization**: Converted artist access requests JavaScript from jQuery to vanilla JavaScript with fetch API for better performance and maintainability
- **Function Name Updates**: Updated artist platform function calls from `bp_*` to `ec_*` equivalents in artist ownership repair and user relationships tools
- **Code Architecture**: Removed legacy AJAX handlers from artist access requests tool in favor of REST API endpoints

### Technical
- **API Improvements**: Enhanced error handling and response consistency across REST endpoints
- **Documentation Structure**: Established comprehensive documentation framework with API patterns, tool-specific guides, and troubleshooting resources
- **Code Quality**: Improved JavaScript code organization and removed jQuery dependencies where possible

## [1.2.4] - 2025-12-13

### Removed
- **Link Page Font Migration Tool**: Removed completed one-time migration tool that normalized link page font values from legacy "WilcoLoftSans" to theme-aligned "Loft Sans"

### Technical
- **Code Cleanup**: Removed obsolete migration tool files (PHP and JavaScript assets)

## [1.2.2] - 2025-12-11

### Added
- **Network Declaration**: Added "Network: true" to plugin header for explicit multisite support

### Changed
- **Scaled Image URL Fix Tool**: Major rewrite for multisite compatibility - now fixes broken -scaled image URLs by adding /sites/{blog_id}/ path and converting to -scaled-1 variant with HTTP verification
- **Block Namespace Migration Tool**: Improved regex patterns for more accurate block namespace replacement, including opening, self-closing, and closing blocks. Migrated to filter-based registration pattern

### Removed
- **Bio Decoupling Migration Tool**: Removed completed one-time migration tool that decoupled artist profile bios from link page bios

### Technical
- **Code Quality**: Improved code formatting and consistency across all modified files
- **Multisite Support**: Enhanced scaled image URL fixing with proper multisite path handling and URL existence verification

## [1.2.1] - 2025-12-11

### Added
- **Bio Decoupling Migration Tool**: One-time migration tool to decouple artist profile bios from link page bios by copying content to dedicated meta fields
- **Block Namespace Migration Tool**: Migration utility to consolidate Gutenberg blocks from plugin-specific namespaces (`extrachill-artist-platform/*`, `extrachill-blocks/*`) to unified `extrachill/*` namespace

### Technical
- **Tool Registration**: Added two new conditional admin tools following existing filter-based registration pattern
- **Data Migration**: Implemented safe one-time migration logic with comprehensive reporting and error handling

## [1.2.0] - 2025-12-10

### Added
- **Scaled Image URL Fix Tool**: New admin tool to scan and fix broken image URLs in post content by replacing with available -scaled versions. Supports regular posts and bbPress forum content with scan/fix operations.

### Changed
- **Dynamic Site Configuration**: Replaced hardcoded blog IDs and URLs with dynamic functions (`ec_get_blog_id()`, `ec_get_site_url()`) for better maintainability across multisite network
- **CSS Standardization**: Migrated all CSS files to use consistent font size variables and standardized indentation for improved maintainability

### Technical
- **Code Quality**: Enhanced multisite compatibility by removing hardcoded site references
- **Styling**: Improved CSS consistency across all tool stylesheets

## [1.1.1] - 2025-12-08

### Removed
- **Social ID Backfill Tool**: Removed the conditional social ID backfill tool that was added in 1.1.0, including both PHP implementation and JavaScript assets

### Changed
- **Admin Interface Styling**: Migrated inline styles to CSS classes (`ec-tool-card`, `ec-tool-description`) for better maintainability and consistency
- **Version Consistency**: Fixed version number inconsistency between plugin header (1.1.0) and composer.json (1.0.6)

### Technical
- **Code Quality**: Improved CSS organization by moving presentation styles to dedicated stylesheet classes

## [1.1.0] - 2025-12-07

### Added
- **Social ID Backfill Tool**: New conditional tool for artist.extrachill.com that performs one-time migration to assign stable social IDs to artist profiles using extrachill-api helpers. Includes dry-run capability and comprehensive reporting.

### Changed
- **REST API Migration**: Migrated all AJAX operations to REST API endpoints for improved architecture and consistency.
  - Lifetime Extra Chill Membership Management: Now uses `/admin/lifetime-membership/grant` and `/admin/lifetime-membership/{userId}` endpoints
  - Artist-User Relationships: Now uses `/users/{userId}/artists/{artistId}`, `/users/search`, and `/users/{userId}/artists` endpoints
  - Team Member Management: Now uses `/admin/team-members/sync` and `/admin/team-members/{userId}` endpoints
- **JavaScript Modernization**: Converted all AJAX calls from jQuery to modern fetch API with improved error handling
- **Admin Interface**: Updated `wp_localize_script` to use REST API URLs and single nonce system

### Technical
- **Database Enhancements**: Improved activation hook with better prepared statements, enhanced 404_log table management, and improved error handling
- **Code Quality**: Added comprehensive PHP docblocks, improved code formatting, and consistent spacing throughout
- **Security**: Enhanced nonce verification and input sanitization across all tools

## [1.0.6] - 2025-12-05

### Added
- **Artist Access Requests Tool**: Admin interface for reviewing and approving artist platform access requests from users. Supports musician and industry professional types with AJAX-powered approval/rejection and email notifications.

### Fixed
- **QR Code Generator**: Removed redundant success check in JavaScript response handling for improved reliability.

## [1.0.5] - 2025-12-04

### Changed
- **QR Code Generator Tool**: Refactored to use extrachill-api REST endpoint instead of local Endroid QR Code library, converted JavaScript from jQuery to vanilla JavaScript

### Removed
- **Analytics URL Cleanup Tool**: Removed the analytics URL cleanup tool added in 1.0.4
- **Endroid QR Code Dependency**: Removed endroid/qr-code Composer dependency

## [1.0.4] - 2025-12-04

### Added
- **Analytics URL Cleanup Tool**: One-time migration to normalize historical link click URLs by removing auto-generated Google Analytics parameters (_gl, _ga, _ga_*). Merges duplicate rows and aggregates click counts. Requires extrachill-artist-platform plugin.

## [1.0.3] - 2025-12-04

### Added
- **Bulk Forum Topic Migration Tool**: Generalized utility to move topics in bulk from one forum to another within the same site, with hierarchical forum support

### Removed
- **Festival Wire Category Cleaner Tool**: Removed after successful category cleanup operations
- **Artist Forum Migration Tool**: Removed after completion of artist forum content migration to community site

## [1.0.2] - 2025-12-04

### Added
- **Festival Wire Category Cleaner**: One-click cleanup tool that removes all category assignments (including “Extra Chill Presents”) from Festival Wire posts

## [1.0.1] - 2025-11-29

### Added
- **QR Code Generator Tool**: Universal QR code generator for any URL with high-resolution print-ready output using Endroid QR Code library
- **Taxonomy Sync Tool**: Synchronize taxonomies from main site to network sites with selective targeting and metadata preservation
- **Lifetime Extra Chill Membership Management Tool**: Grant, revoke, and manage Lifetime Extra Chill Memberships with user search and AJAX-powered interface (ad-free)
- **Artist-User Relationships Tool**: Comprehensive management interface for artist-user relationships with orphan detection and cleanup
- **Team Member Management Tool**: Sync team member status from main site with manual override support
- **Artist Ownership Repair Tool**: Repair and maintain artist ownership relationships
- **Artist Forum Repair Tool**: Synchronize and repair artist forum relationships
- **404 Error Logger Tool**: Log 404 errors with daily email reports, custom database table, and automatic cleanup
- **Tag Migration Tool**: Bulk migrate tags to festival, artist, or venue taxonomies with search and pagination
- **Tabbed Navigation System**: Replaced list-based interface with tabbed navigation for better UX
- **AJAX-Powered Interfaces**: Multiple tools now use AJAX for real-time operations and better performance
- **Composer Dependency Management**: Added Endroid QR Code library and development tools
- **Network-Wide Database Support**: Enhanced 404 logging with blog_id tracking for multisite

### Changed
- **Admin Interface**: Migrated from list-based to tabbed navigation system
- **Database Schema**: Updated 404_log table with blog_id column for network-wide tracking
- **Security Enhancements**: Improved nonce verification and input sanitization across all tools
- **Code Organization**: Modular CSS and JavaScript files for better maintainability

### Removed
- **Artist Platform Migration Tool**: Removed after successful migration completion
- **Scaled Image URL Fixer Tool**: Removed after one-time operation completion
- **Festival Wire Migration Tool**: Removed after migration completion
- **Legacy Tools**: Various unused migration and cleanup tools removed

### Technical
- **Dependencies**: Added Endroid QR Code (^6.0) for QR code generation
- **Database**: Custom 404_log table with varchar(2000) fields for long URLs
- **Security**: Administrator-only access with manage_options capability checks
- **AJAX**: Nonce verification and prepared statements for all database operations
- **Build System**: Composer-based dependency management with production build script