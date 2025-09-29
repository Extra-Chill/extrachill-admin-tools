# Extra Chill Admin Tools

Centralized administrative tools for the Extra Chill platform WordPress multisite network.

## Features

**Production Status**: Active plugin with 4 administrative tools

### Tools
- **Tag Migration** - Bulk migrate tags to festival, artist, or venue taxonomies
- **404 Error Logger** - Log 404 errors with daily email reports and cleanup
- **Festival Wire Migration** - One-time migration utilities (requires extrachill-news-wire plugin)
- **Session Token Cleanup** - Clean up legacy session token tables

### Security
- Administrator-only access with capability checks
- WordPress nonce verification for all forms
- Input sanitization and prepared database statements

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

## License

GPL v2 or later

## Author

**Chris Huber** - [chubes.net](https://chubes.net) | [GitHub](https://github.com/chubes4) | [Extra Chill](https://extrachill.com)