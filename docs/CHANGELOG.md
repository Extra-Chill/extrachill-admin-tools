# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1/0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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