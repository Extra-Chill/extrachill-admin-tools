# Extra Chill Admin Tools

Centralized administrative tools for the Extra Chill platform ecosystem. Provides unified admin functionality across the WordPress multisite network.

## 🔧 Overview

The Extra Chill Admin Tools plugin consolidates administrative functionality for the entire ExtraChill platform, providing centralized management tools for the multisite network.

## ⚠️ Current Status

**ACTIVE DEVELOPMENT**

- **Plugin Structure**: Complete plugin framework with proper file organization
- **Admin Interface**: Settings page with tool registration system
- **Core Tools**: Tag migration, 404 error logging, Festival Wire migration, session token cleanup
- **Safety**: Plugin dependency checks ensure tools only load when required plugins are active

## 🚀 Features

### Current Implementation
- **Tag Migration**: Bulk migrate tags to festival, artist, or venue taxonomies with search and pagination
- **404 Error Logger**: Logs 404 errors with daily email reports and automatic cleanup
- **Festival Wire Migration**: One-time migration tools for tags and authors (requires extrachill-news-wire plugin)
- **Session Token Cleanup**: Admin notice and cleanup utility for legacy session token tables
- **Plugin Safety**: Conditional loading based on plugin dependencies

### Planned Features
- **Multisite Management**: Cross-site administration tools
- **User Management**: Bulk user operations and profile management
- **Content Administration**: Bulk content operations and maintenance
- **System Monitoring**: Platform health and performance monitoring
- **Plugin Integration**: Unified interface for all ExtraChill plugins

## 🏗️ Architecture

### Current Structure
```
extrachill-admin-tools/
├── extrachill-admin-tools.php   # Main plugin file
└── inc/
    ├── admin/
    │   └── admin-settings.php          # Admin interface and settings page
    └── tools/
        ├── tag-migration.php           # Tag migration utilities
        ├── 404-error-logger.php        # 404 error logging system
        ├── festival-wire-migration.php # Festival Wire migration (conditional)
        └── session-token-cleanup.php   # Session token cleanup utility
```

### Plugin Pattern
- **Procedural Architecture**: Simple WordPress plugin development
- **Tool Registration**: Filter-based system for registering admin tools
- **Conditional Loading**: Tools only load when required plugins are active
- **Security**: Administrator-only access with proper capability checks
- **Separation of Concerns**: Admin interface separate from tool implementations

## 📦 Installation

### Prerequisites
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Admin Access**: Requires administrator privileges

### Setup
1. Upload plugin files to `/wp-content/plugins/extrachill-admin-tools/`
2. Activate plugin in WordPress admin
3. Access via `Settings > Extra Chill Admin`

## 🔒 Security

### Current Security Implementation
- **Capability Checks**: Restricts access to administrators only
- **Permission Validation**: Proper WordPress capability system usage
- **Admin Protection**: Direct access prevention with ABSPATH checks

### Planned Security Enhancements
- **Nonce Verification**: All forms will use WordPress nonce system
- **Input Sanitization**: Comprehensive sanitization for admin tools
- **Audit Logging**: Administrative action tracking
- **Granular Permissions**: Fine-grained permission system

## 🛠️ Development

### Current Limitations
- **No Build System**: Basic WordPress plugin without build tools
- **No Dependencies**: Self-contained implementation
- **Basic Testing**: No automated test framework implemented
- **Manual Operations**: Most tools require manual admin intervention

### Future Development Commands
```bash
# Planned commands (not yet implemented)
composer install                 # Install dependencies
composer run lint:php           # PHP linting
./build.sh                       # Create production build
npm run test                     # Run test suite
```

## 🔌 Integration

### ExtraChill Ecosystem Integration (Planned)
- **Artist Platform**: Bulk artist profile management tools
- **Community Tools**: Forum moderation and user management
- **Shop Administration**: E-commerce and license management
- **Newsletter Management**: Subscriber and campaign administration
- **Multisite Tools**: Cross-domain administrative operations

### WordPress Multisite (Planned)
- **Network Administration**: Tools for entire multisite network
- **Cross-Site Operations**: Administrative tasks across all ExtraChill sites
- **Unified User Management**: Centralized user administration
- **Performance Monitoring**: Network-wide performance tracking

## 📊 Admin Interface

### Current Interface
- **Location**: `Settings > Extra Chill Admin`
- **Access**: Administrator capability required
- **Content**: Basic placeholder page
- **Security**: Proper permission validation

### Planned Interface Enhancements
- **Dashboard**: Unified admin dashboard for all ExtraChill functionality
- **Tool Categories**: Organized admin tools by functionality
- **Quick Actions**: Common administrative tasks
- **Analytics**: Platform-wide reporting and analytics

## 🗺️ Development Roadmap

### Phase 1: Foundation
- [x] Basic plugin structure
- [x] Admin menu integration
- [x] Security implementation
- [ ] Core admin dashboard

### Phase 2: Core Tools
- [ ] User management utilities
- [ ] Content administration tools
- [ ] Basic multisite management
- [ ] Plugin integration framework

### Phase 3: Advanced Features
- [ ] Analytics dashboard
- [ ] Performance monitoring
- [ ] Automated maintenance
- [ ] Data export/import

### Phase 4: Full Integration
- [ ] Complete ExtraChill plugin integration
- [ ] Advanced workflow automation
- [ ] Custom reporting tools
- [ ] Mobile admin interface

## 🤝 Contributing

### Development Guidelines
- **WordPress Standards**: Follow WordPress coding standards
- **Security First**: Implement comprehensive security measures
- **Modular Design**: Organize functionality into logical modules
- **Performance Focus**: Ensure admin tools don't impact site performance

### Code Quality
- **Capability Checks**: All admin functions require proper permissions
- **Input Validation**: Sanitize and validate all user input
- **Error Handling**: Graceful error handling and user feedback
- **Documentation**: Clear documentation for all admin tools

## 📄 License

GPL v2 or later - Compatible with WordPress licensing requirements.

## 👤 Author

**Chris Huber**
- Website: [chubes.net](https://chubes.net)
- GitHub: [@chubes4](https://github.com/chubes4)
- Extra Chill: [extrachill.com](https://extrachill.com)

## 🔗 Related Plugins

- **[ExtraChill Artist Platform](../extrachill-artist-platform/)** - Artist management and profiles
- **[ExtraChill Community](../extrachill-community/)** - Forum and community features
- **[ExtraChill Shop](../extrachill-shop/)** - E-commerce functionality
- **[ExtraChill Multisite](../extrachill-multisite/)** - Network-wide functionality

---

*Part of the ExtraChill Platform - Centralized administrative tools for the complete music community ecosystem.*