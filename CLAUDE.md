# Extra Chill Admin Tools

WordPress plugin providing centralized administrative tools for the Extra Chill platform multisite network.

## Current Implementation

**Status**: Production plugin with 4 active tools
**Location**: Tools > Admin Tools (administrator-only access)
**Architecture**: Filter-based tool registration with conditional loading

### Core Structure
- `extrachill-admin-tools.php` - Main plugin file with initialization
- `inc/admin/admin-settings.php` - Admin interface and tool registration
- `inc/tools/` - Individual tool implementations

## Tools

### Tag Migration (`inc/tools/tag-migration.php`)
Bulk migrate tags to festival, artist, or venue taxonomies with search, pagination, and reporting.

### 404 Error Logger (`inc/tools/404-error-logger.php`)
Logs 404 errors with daily email reports. Custom `404_log` table with automatic cleanup. Excludes `/event/` URLs.

### Festival Wire Migration (`inc/tools/festival-wire-migration.php`)
One-time Festival Wire content migration. Only loads when `extrachill-news-wire` plugin active.

### Session Token Cleanup (`inc/tools/session-token-cleanup.php`)
Admin notice and cleanup for legacy session token tables. AJAX table removal with confirmation.

## Development

### Commands
```bash
composer install && composer run lint:php  # Install deps and lint
composer run lint:fix                      # Fix coding standards
composer test                              # Run tests
./build.sh                                 # Create production ZIP
```

### Architecture
- Procedural WordPress plugin with filter-based tool registration
- Tools in `inc/tools/` load conditionally based on plugin dependencies
- All operations require `manage_options` capability with nonce verification

## Security

- Administrator-only access with `manage_options` capability checks
- WordPress nonce system for CSRF protection
- Input sanitization and prepared database statements
- AJAX handlers with nonce verification