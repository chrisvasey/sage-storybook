<?php

namespace ChrisVasey\SageStorybookBlade\Tests\TestCase;

use ChrisVasey\SageStorybookBlade\StorybookServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class PackageTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up views directory
        $this->app['config']->set('view.paths', [
            __DIR__.'/../fixtures/views',
            resource_path('views'),
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            StorybookServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.debug', true);

        // Set storybook config early
        $app['config']->set('storybook.enabled', true);
        $app['config']->set('storybook.allowed_prefixes', ['components', 'blocks', 'partials']);
        $app['config']->set('storybook.allowed_environments', ['testing']);
        $app['config']->set('storybook.route_prefix', 'storybook');
        $app['config']->set('storybook.require_debug', false);
        $app['config']->set('storybook.version', '1.0.0');
    }

    /**
     * Create a test view file
     */
    protected function createTestView(string $path, string $content): void
    {
        $fullPath = __DIR__.'/../fixtures/views/'.str_replace('.', '/', $path).'.blade.php';
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, $content);
    }

    /**
     * Clean up test views
     */
    protected function tearDown(): void
    {
        // Clean up fixtures
        $fixturesDir = __DIR__.'/../fixtures';
        if (is_dir($fixturesDir)) {
            $this->deleteDirectory($fixturesDir);
        }

        parent::tearDown();
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
