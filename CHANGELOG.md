# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-XX

### Added
- Initial release of Sage Storybook integration package
- Server-side Blade component rendering via HTTP API  
- Storybook 8.0+ support with HTML framework
- Interactive component development and documentation
- Artisan installation command (`storybook:install`)
- Configurable security and CORS settings
- Component caching and performance optimisations
- Theme asset integration with Vite
- Multi-environment support (local, development, staging)
- Component introspection and metadata extraction
- Example stories and comprehensive documentation

### Features
- `StorybookService` - Core service for component rendering and management
- `StorybookController` - HTTP API endpoints for Storybook integration
- `StorybookServiceProvider` - Laravel service provider with auto-discovery
- `StorybookInstallCommand` - Automated installation and setup
- JavaScript `blade-loader` - Client-side component rendering utilities
- Configurable component path prefixes (components, blocks, partials)
- Built-in error handling and debugging
- Theme CSS/JS asset loading decorator

### Documentation
- Comprehensive README with installation and usage instructions
- API endpoint documentation
- JavaScript utility documentation  
- Troubleshooting guide
- Configuration examples
- Security best practices