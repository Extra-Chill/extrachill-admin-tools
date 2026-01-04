# Extra Chill Admin Tools

Centralized administrative tools for the Extra Chill platform WordPress multisite network.

## Features

**Production Status**: Active plugin with 11 tabbed tools

### Tabbed Interface Tools
- **Tag Migration** - Bulk migrate tags to festival, artist, or venue taxonomies with search and pagination
- **404 Error Logger** - Log 404 errors with daily email reports, automatic cleanup, and custom database table
- **Team Member Management** - Sync team members from main site with manual override support (conditional: requires extrachill-users)
- **Artist-User Relationships** - Manage relationships between users and artist profiles with orphan detection (conditional: requires extrachill-artist-platform)
- **Artist Ownership Repair** - Repair ownership relationships between artists and users
- **Artist Forum Repair** - Repair and synchronize artist forum relationships
- **QR Code Generator** - Generate QR codes via REST API with real-time preview and download
- **Lifetime Extra Chill Membership Management** - Grant, revoke, and manage Lifetime Extra Chill Memberships (ad-free) for platform users
- **Taxonomy Sync** - Synchronize taxonomies from main site to other network sites
- **Forum Topic Migration** - Migrate forum topics between forums
- **Artist Access Requests** - Manage artist access approval and rejection requests

### Conditional Loading
Tools load based on context:
- **Plugin dependencies**: Artist-User Relationships (requires extrachill-artist-platform), Team Member Management (requires extrachill-users)

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