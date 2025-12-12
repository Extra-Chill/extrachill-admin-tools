# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1/0/0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
  - Ad-Free License Management: Now uses `/admin/ad-free-license/grant` and `/admin/ad-free-license/{userId}` endpoints
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
- **Ad-Free License Management Tool**: Grant, revoke, and manage ad-free licenses with user search and AJAX-powered interface
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