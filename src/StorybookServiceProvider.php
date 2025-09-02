<?php

namespace ChrisVasey\SageStorybookBlade;

use ChrisVasey\SageStorybookBlade\Console\Commands\StorybookInstallCommand;
use ChrisVasey\SageStorybookBlade\Http\Controllers\StorybookController;
use ChrisVasey\SageStorybookBlade\Services\StorybookService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StorybookServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/storybook.php', 'storybook');

        $this->app->singleton(StorybookService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerCommands();
        $this->registerPublishing();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        // Only register routes if enabled and in appropriate environment
        if (! $this->shouldRegisterRoutes()) {
            return;
        }

        Route::group([
            'prefix' => config('storybook.route_prefix', 'storybook'),
            'middleware' => config('storybook.middleware', []),
        ], function () {
            // Health check
            Route::get('/health', [StorybookController::class, 'health']);

            // List all available components
            Route::get('/components', [StorybookController::class, 'list']);

            // Get component metadata
            Route::get('/components/{component}/metadata', [StorybookController::class, 'metadata'])
                ->where('component', '.*');

            // Render a component
            Route::post('/render/{component}', [StorybookController::class, 'render'])
                ->where('component', '.*');

            // Handle CORS preflight requests
            Route::options('/{any}', [StorybookController::class, 'options'])
                ->where('any', '.*');
        });
    }

    /**
     * Register the package commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                StorybookInstallCommand::class,
            ]);
        }
    }

    /**
     * Register the package publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/storybook.php' => config_path('storybook.php'),
            ], 'storybook-config');

            // Publish stubs
            $this->publishes([
                __DIR__.'/../resources/stubs' => base_path('stubs/storybook'),
            ], 'storybook-stubs');

            // Publish JavaScript assets
            $this->publishes([
                __DIR__.'/../resources/js' => resource_path('js/storybook'),
            ], 'storybook-js');
        }
    }

    /**
     * Determine if routes should be registered.
     */
    protected function shouldRegisterRoutes(): bool
    {
        if (! config('storybook.enabled', true)) {
            return false;
        }

        // Check environment restrictions
        $allowedEnvironments = config('storybook.allowed_environments', ['local', 'development']);
        
        if (! empty($allowedEnvironments) && ! app()->environment($allowedEnvironments)) {
            return false;
        }

        // Check debug mode requirement
        if (config('storybook.require_debug', false) && ! config('app.debug')) {
            return false;
        }

        return true;
    }
}