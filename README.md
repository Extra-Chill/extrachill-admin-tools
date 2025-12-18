# Extra Chill Admin Tools

Centralized administrative tools for the Extra Chill platform WordPress multisite network.

## Features

**Production Status**: Active plugin with 9 tools (7 tabbed interface tools + 2 admin notice tools)

### Tabbed Interface Tools
- **Tag Migration** - Bulk migrate tags to festival, artist, or venue taxonomies with search and pagination
- **404 Error Logger** - Log 404 errors with daily email reports, automatic cleanup, and custom database table
- **Festival Wire Migration** - One-time Festival Wire content migration (conditional: requires extrachill-news-wire plugin)
- **Artist Platform Migration** - Direct site-to-site migration from community.extrachill.com to artist.extrachill.com (site-specific: artist.extrachill.com only)
- **Artist-User Relationships** - Manage relationships between users and artist profiles with orphan detection (conditional: requires extrachill-artist-platform)
- **Team Member Management** - Sync team members from main site with manual override support (conditional: requires extrachill-multisite)
- **User Links Migration** - Convert legacy static social link fields to dynamic array system (note: file exists but not currently loaded)

### Admin Notice Tools
- **Session Token Cleanup** - Admin notice with AJAX cleanup for legacy session token tables
- **Image Votes Cleanup** - Admin notice for removing legacy image_votes database table after block migration

### Conditional Loading
Tools load based on context:
- **Site-specific**: Artist Platform Migration (artist.extrachill.com only)
- **Plugin dependencies**: Artist-User Relationships (requires extrachill-artist-platform), Team Member Management (requires extrachill-multisite), Festival Wire Migration (requires extrachill-news-wire)

### Security
- Administrator-only access with `manage_options` capability checks
- WordPress nonce verification for all forms and AJAX requests
- Input sanitization and output escaping throughout
- Prepared database statements for all queries
- Double confirmation prompts for destructive operations

## Installation

**Requirements**: WordPress 5.0+, PHP 7.4+, Administrator access

1. Upload plugin files to `/wp-content/plugins/extrachill-admin-tools/`
2. Activate plugin in WordPress admin
3. Access via `Tools > Admin Tools`

## Development

```bash
composer install               # Install dependencies
composer run lint:php         # PHP linting
composer run lint:fix          # Fix coding standards
composer test                  # Run tests
./build.sh                     # Create production build
```

## Comprehensive Documentation

For detailed tool documentation, database schemas, and API specifications, see:
- `/docs/tools/` - Individual tool documentation with examples and use cases
- `/docs/api-patterns.md` - Common AJAX and REST API patterns used throughout
- `/docs/CHANGELOG.md` - Version history and updates

## License

GPL v2 or later

## Author

**Chris Huber** - [chubes.net](https://chubes.net) | [GitHub](https://github.com/chubes4) | [Extra Chill](https://extrachill.com)