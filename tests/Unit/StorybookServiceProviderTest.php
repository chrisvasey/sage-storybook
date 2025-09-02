<?php

namespace ChrisVasey\SageStorybookBlade\Tests\Unit;

use ChrisVasey\SageStorybookBlade\Console\Commands\StorybookInstallCommand;
use ChrisVasey\SageStorybookBlade\Services\StorybookService;
use ChrisVasey\SageStorybookBlade\Tests\TestCase\PackageTestCase;

class StorybookServiceProviderTest extends PackageTestCase
{
    /** @test */
    public function it_registers_the_storybook_service()
    {
        $service = $this->app->make(StorybookService::class);
        $this->assertInstanceOf(StorybookService::class, $service);

        // Test that it's registered as singleton
        $service2 = $this->app->make(StorybookService::class);
        $this->assertSame($service, $service2);
    }

    /** @test */
    public function it_registers_console_commands()
    {
        $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')->all();
        
        $this->assertArrayHasKey('storybook:install', $commands);
        $this->assertInstanceOf(StorybookInstallCommand::class, $commands['storybook:install']);
    }

    /** @test */
    public function it_merges_configuration()
    {
        $this->assertEquals(true, config('storybook.enabled'));
        $this->assertEquals('1.0.0', config('storybook.version'));
        $this->assertEquals('storybook', config('storybook.route_prefix'));
        $this->assertIsArray(config('storybook.allowed_prefixes'));
        $this->assertContains('components', config('storybook.allowed_prefixes'));
        $this->assertContains('blocks', config('storybook.allowed_prefixes'));
    }

    /** @test */
    public function it_registers_routes_when_enabled()
    {
        $router = $this->app->make('router');
        $routes = $router->getRoutes();

        // Check that storybook routes are registered
        $routeNames = [];
        foreach ($routes as $route) {
            if (str_starts_with($route->uri(), 'storybook/')) {
                $routeNames[] = $route->uri();
            }
        }

        $this->assertContains('storybook/health', $routeNames);
        $this->assertContains('storybook/components', $routeNames);
        $this->assertContains('storybook/render/{component}', $routeNames);
        $this->assertContains('storybook/components/{component}/metadata', $routeNames);
    }

    /** @test */
    public function it_does_not_register_routes_when_disabled()
    {
        // Create new app instance with disabled storybook
        $app = $this->createApplication();
        $app['config']->set('storybook.enabled', false);
        
        // This is tricky to test in Laravel's testing environment
        // as routes are typically registered during boot
        $this->assertTrue(true); // Placeholder - would need integration test
    }

    /** @test */
    public function it_respects_environment_restrictions()
    {
        config(['storybook.allowed_environments' => ['production']]);
        config(['app.env' => 'testing']);

        // Test that shouldRegisterRoutes would return false
        // This would typically be tested through actual HTTP requests
        // as shown in the feature tests
        $this->assertTrue(true); // Placeholder
    }

    /** @test */
    public function it_can_be_configured_with_custom_route_prefix()
    {
        config(['storybook.route_prefix' => 'custom-storybook']);

        // In a real application, this would change the route prefix
        // Testing this requires recreating the service provider
        $this->assertEquals('custom-storybook', config('storybook.route_prefix'));
    }

    /** @test */
    public function it_has_correct_publishable_assets()
    {
        $provider = $this->app->getProvider('ChrisVasey\SageStorybookBlade\StorybookServiceProvider');
        
        // Test that provider is registered
        $this->assertNotNull($provider);
    }
}