# Sage Storybook

[![Tests](https://github.com/chrisvasey/sage-storybook/workflows/Tests/badge.svg)](https://github.com/chrisvasey/sage-storybook/actions)
[![Latest Stable Version](https://poser.pugx.org/chrisvasey/sage-storybook/v/stable)](https://packagist.org/packages/chrisvasey/sage-storybook)
[![Total Downloads](https://poser.pugx.org/chrisvasey/sage-storybook/downloads)](https://packagist.org/packages/chrisvasey/sage-storybook)
[![License](https://poser.pugx.org/chrisvasey/sage-storybook/license)](https://packagist.org/packages/chrisvasey/sage-storybook)

A modern Storybook integration for [Roots Sage](https://roots.io/sage/) WordPress themes that allows you to develop, test, and document your Blade components in isolation.

## Features

- 🚀 **Server-side rendering** of Blade components via HTTP API
- 🎨 **Theme integration** with your Sage theme's CSS and assets  
- 🔧 **Hot reloading** and component introspection
- 📖 **Interactive documentation** with Storybook's full feature set
- ⚡ **Vite integration** for fast development
- 🛡️ **Security-focused** with configurable environment restrictions

## Requirements

- PHP 8.2+
- Laravel/Illuminate 10.0+ or 11.0+
- Roots Sage theme with Acorn
- Node.js 18+
- Storybook 8.0+

### WordPress Plugins (for blocks support)

To use Gutenberg blocks in Storybook, you'll need these plugins installed:

- **Advanced Custom Fields Pro** - Core field functionality
  ```bash
  composer require wpengine/advanced-custom-fields-pro
  ```

- **ACF Composer** - Programmatic field management (recommended)
  ```bash
  composer require log1x/acf-composer
  ```

These plugins enable the `blocks.*` component prefix in Storybook and allow you to develop and document your custom Gutenberg blocks alongside regular Blade components.

## Installation

### 1. Install the Composer package

```bash
composer require --dev chrisvasey/sage-storybook
```

### 2. Run the installation command

```bash
wp acorn storybook:install
```

This will:
- Create the `.storybook/` directory with configuration files
- Update your `package.json` with required dependencies
- Create an example story
- Publish the configuration file

### 3. Configure your site URL

Edit `.storybook/preview.js` and update the configuration:

```javascript
configure({
  apiBaseUrl: 'https://your-site.test', // Your local development URL
});
```

### 4. Build your theme assets

```bash
npm run build
```

### 5. Start Storybook

```bash
npm run storybook
```

Visit `http://localhost:6006` to see your Storybook instance.

## Creating Stories

Stories are created using Storybook's Component Story Format (CSF). Here's a basic example:

```javascript
// resources/stories/components/Button.stories.js
import { renderBlade } from '@storybook/blade-loader';

export default {
  title: 'Components/Button',
  render: renderBlade,
  parameters: {
    server: {
      component: 'components.button', // Path to your Blade component
    },
  },
  argTypes: {
    text: { control: 'text' },
    variant: { control: 'select', options: ['primary', 'secondary'] },
    disabled: { control: 'boolean' },
  },
  args: {
    text: 'Click me',
    variant: 'primary',
    disabled: false,
  },
};

export const Default = {};

export const Secondary = {
  args: { variant: 'secondary' },
};

export const Disabled = {
  args: { disabled: true },
};
```

### Component Requirements

For best results, ensure your Blade components:

1. **Use default values for props:**
   ```blade
   @php
   $text = $text ?? 'Default Text';
   $variant = $variant ?? 'primary';
   @endphp
   ```

2. **Handle slots gracefully:**
   ```blade
   <button class="btn btn-{{ $variant }}">
       {{ $slot ?? $text }}
   </button>
   ```

3. **Are self-contained** (don't rely on WordPress-specific context)

### Working with Gutenberg Blocks

If you're using ACF Composer for Gutenberg blocks, you can document them in Storybook too:

```javascript
// resources/stories/blocks/Title.stories.js
import { renderBlade } from '@storybook/blade-loader';

export default {
  title: 'Blocks/Title',
  render: renderBlade,
  parameters: {
    server: {
      component: 'blocks.title', // Path to your block's Blade template
    },
  },
  argTypes: {
    title: { control: 'text' },
    background_image: { control: 'text' },
    text_colour: { control: 'color' },
  },
  args: {
    title: 'Sample Title',
    background_image: '',
    text_colour: '#000000',
  },
};

export const Default = {};
export const WithBackground = {
  args: {
    background_image: 'https://via.placeholder.com/1200x400',
  },
};
```

**Note:** Blocks require the ACF and ACF Composer packages to be installed and configured in your WordPress site.

## Component Organisation

Sage Storybook automatically discovers components in these directories:

- **`resources/views/components/`** - Standard Blade components
  - Stories: `components.button`, `components.card`, etc.

- **`resources/views/blocks/`** - ACF Composer Gutenberg blocks  
  - Stories: `blocks.title`, `blocks.hero`, etc.
  - Requires ACF Pro + ACF Composer

- **`resources/views/partials/`** - Theme partials and includes
  - Stories: `partials.header`, `partials.footer`, etc.

You can customise these paths in the configuration file or add additional prefixes as needed.

## Configuration

The package can be configured via the published config file `config/storybook.php`:

```php
<?php

return [
    // Enable/disable Storybook functionality
    'enabled' => env('STORYBOOK_ENABLED', true),
    
    // Environment restrictions
    'allowed_environments' => ['local', 'development', 'staging'],
    
    // Route prefix for API endpoints
    'route_prefix' => env('STORYBOOK_ROUTE_PREFIX', 'storybook'),
    
    // Component path prefixes
    'allowed_prefixes' => [
        'components', // Standard Blade components
        'blocks',     // ACF Composer Gutenberg blocks
        'partials',   // Theme partials/includes
    ],
    
    // CORS configuration
    'cors' => [
        'allowed_origins' => ['*'],
    ],
    
    // Frontend configuration  
    'frontend' => [
        'api_base_url' => env('STORYBOOK_API_BASE_URL', env('WP_HOME')),
        'assets_url' => env('STORYBOOK_ASSETS_URL'),
    ],
];
```

### Environment Variables

Add these to your `.env` file:

```bash
# Enable/disable Storybook
STORYBOOK_ENABLED=true

# API configuration
STORYBOOK_API_BASE_URL=https://your-site.test
STORYBOOK_ROUTE_PREFIX=storybook

# Security
STORYBOOK_REQUIRE_DEBUG=false
```

## API Endpoints

The package provides several API endpoints for Storybook integration:

- `GET /storybook/health` - Health check
- `GET /storybook/components` - List all available components  
- `GET /storybook/components/{component}/metadata` - Get component metadata
- `POST /storybook/render/{component}` - Render a component with props

## JavaScript API

The package provides several JavaScript utilities:

### `renderBlade(args, context)`

The main render function for stories:

```javascript
import { renderBlade } from '@storybook/blade-loader';

export default {
  render: renderBlade,
  // ... rest of story config
};
```

### `configure(options)`

Configure the Blade loader:

```javascript
import { configure } from '@storybook/blade-loader';

configure({
  apiBaseUrl: 'https://your-site.test',
  assetsUrl: 'https://your-site.test/build/assets/app.css',
});
```

### `withSageAssets` decorator

Automatically loads your theme's CSS:

```javascript
import { withSageAssets } from '@storybook/blade-loader';

export default {
  decorators: [withSageAssets],
  // ... rest of story config
};
```

### `clearBladeCache()`

Clear the component render cache (useful during development):

```javascript
import { clearBladeCache } from '@storybook/blade-loader';

// Clear cache manually
clearBladeCache();

// Or use the global function in browser console
window.clearBladeCache();
```

## Advanced Usage

### Custom Middleware

Add custom middleware to Storybook routes:

```php
// config/storybook.php
return [
    'middleware' => ['auth:admin'], // Example: require admin authentication
];
```

### Multiple Theme Support

Configure different API endpoints for different themes:

```javascript
// .storybook/preview.js
const isThemeA = window.location.hostname.includes('theme-a');

configure({
  apiBaseUrl: isThemeA 
    ? 'https://theme-a.test' 
    : 'https://theme-b.test',
});
```

### Component Caching

Enable caching for better performance:

```php
// config/storybook.php
return [
    'cache' => [
        'enabled' => true,
        'ttl' => 300, // 5 minutes
    ],
];
```

## Troubleshooting

### Component Not Rendering

1. **Check the API endpoint** is accessible:
   ```bash
   curl https://your-site.test/storybook/health
   ```

2. **Verify component exists** at the specified path
3. **Check browser console** for errors
4. **Ensure theme assets are built**: `npm run build`

### CORS Issues

If you encounter CORS issues:

1. **Check allowed origins** in `config/storybook.php`
2. **Verify WordPress is running** on correct URL
3. **Clear caches**: `wp acorn optimize:clear`

### Styles Not Loading

1. **Build theme assets**: `npm run build`
2. **Check asset URL** in `.storybook/preview.js` 
3. **Verify CSS path** exists and is accessible

## Development

### Running Tests

The package comes with a comprehensive test suite covering unit tests, feature tests, and integration tests:

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# Run specific test suites
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature

# Check code style
composer lint

# Fix code style
composer format
```

#### Test Coverage

The test suite includes:

- **Unit Tests**: Core service logic, component rendering, metadata extraction
- **Feature Tests**: HTTP endpoints, CORS headers, error handling  
- **Integration Tests**: Complete workflows with realistic components
- **Console Tests**: Installation command, file publishing, configuration

#### Running Tests Locally

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`

### Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass: `composer test`
5. Check code style: `composer lint`
6. Commit your changes (`git commit -m 'Add some amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

All contributions must include tests and pass the existing test suite.

## Security

If you discover any security-related issues, please email security@roots.io instead of using the issue tracker.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

- Built for the [Roots](https://roots.io) ecosystem
- Powered by [Storybook](https://storybook.js.org/)
- Inspired by the Laravel and WordPress communities

---

Made with ❤️ by the Roots team