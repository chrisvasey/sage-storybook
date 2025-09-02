<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storybook Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Sage Storybook Blade
    | integration package.
    |
    */

    /**
     * Enable or disable Storybook routes and functionality
     */
    'enabled' => env('STORYBOOK_ENABLED', true),

    /**
     * Package version
     */
    'version' => '1.0.0',

    /**
     * Route configuration
     */
    'route_prefix' => env('STORYBOOK_ROUTE_PREFIX', 'storybook'),

    /**
     * Middleware to apply to Storybook routes
     */
    'middleware' => [],

    /**
     * Environment restrictions
     * Set to empty array to allow all environments
     */
    'allowed_environments' => ['local', 'development', 'staging'],

    /**
     * Require debug mode to be enabled
     */
    'require_debug' => env('STORYBOOK_REQUIRE_DEBUG', false),

    /**
     * Component path configuration
     */
    'allowed_prefixes' => [
        'components',
        'blocks',
        'partials',
    ],

    /**
     * CORS configuration
     */
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Accept', 'Authorization'],
        'max_age' => 86400,
    ],

    /**
     * Caching configuration
     */
    'cache' => [
        'enabled' => env('STORYBOOK_CACHE_ENABLED', false),
        'ttl' => env('STORYBOOK_CACHE_TTL', 300), // 5 minutes
        'key_prefix' => 'storybook',
    ],

    /**
     * Frontend configuration
     */
    'frontend' => [
        'api_base_url' => env('STORYBOOK_API_BASE_URL', env('WP_HOME', 'http://localhost')),
        'assets_url' => env('STORYBOOK_ASSETS_URL'),
    ],

    /**
     * Story configuration
     */
    'stories' => [
        'path' => 'resources/stories',
        'pattern' => '**/*.stories.@(js|jsx|ts|tsx|mdx)',
    ],
];