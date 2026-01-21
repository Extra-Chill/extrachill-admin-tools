# Extra Chill Admin Tools

Centralized administrative tools for the Extra Chill platform WordPress multisite network.

## Features

**Production Status**: Active plugin with 11 tabbed tools

### Tabbed Interface Tools
- **Tag Migration** - Bulk migrate tags to festival, artist, or venue taxonomies with search and pagination
- **404 Error Logger** - Log 404 errors with daily email reports, automatic cleanup, and custom database table
- **Team Member Management** - Sync team members from main site with manual override support (React-based)
- **Artist-User Relationships** - Manage relationships between users and artist profiles with orphan detection (React-based)
- **Artist Ownership Repair** - Repair ownership relationships between artists and users
- **Artist Forum Repair** - Repair and synchronize artist forum relationships
- **QR Code Generator** - Generate QR codes via REST API with real-time preview and download
- **Lifetime Extra Chill Membership Management** - Grant, revoke, and manage Lifetime Extra Chill Memberships (ad-free) for platform users (React-based)
- **Taxonomy Sync** - Synchronize taxonomies from main site to other network sites
- **Forum Topic Migration** - Migrate forum topics between forums
- **Artist Access Requests** - Manage artist access approval and rejection requests

### Conditional Loading
Tools load based on context:
- **Plugin dependencies**: Artist-User Relationships (requires extrachill-artist-platform), Team Member Management (requires extrachill-users)

### Security
- Administrator-only access with `manage_network_options` capability checks
- Standardized REST API authentication for all tools
- Input sanitization and output escaping throughout
- Prepared database statements for all queries
- Double confirmation prompts for destructive operations

## Deployment

This plugin is deployed as part of the Extra Chill Platform and is network-activated.

Deployments and remote operations run through **Homeboy** (`homeboy/` in this repo).

## Development

The admin interface is a React single-page application.

```bash
composer install               # Install PHP dependencies
npm install                   # Install JS dependencies
npm run start                 # Start dev server
npm run build                 # Create production JS bundle
composer run lint:php         # PHP linting
composer test                 # Run tests
./build.sh                    # Create production ZIP build
```

Production deployments use Homeboy after generating the ZIP artifact.

## Comprehensive Documentation

For detailed tool documentation, database schemas, and API specifications, see:
- [Tag Migration](docs/tools/tag-migration.md)
- [Taxonomy Sync](docs/tools/taxonomy-sync.md)
- [QR Code Generator](docs/tools/qr-code-generator.md)
- [404 Error Logger](docs/tools/404-error-logger.md)
- [Artist-User Relationships](docs/tools/artist-user-relationships.md)
- [API Patterns & Architecture](docs/api-patterns.md)
- [Changelog](docs/CHANGELOG.md)

## License

GPL v2 or later

## Author

**Chris Huber** - [chubes.net](https://chubes.net) | [GitHub](https://github.com/chubes4) | [Extra Chill](https://extrachill.com)