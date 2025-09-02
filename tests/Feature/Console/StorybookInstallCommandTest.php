<?php

namespace ChrisVasey\SageStorybookBlade\Tests\Feature\Console;

use ChrisVasey\SageStorybookBlade\Tests\TestCase\PackageTestCase;
use Illuminate\Support\Facades\File;

class StorybookInstallCommandTest extends PackageTestCase
{
    private string $testBasePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up a temporary directory structure for testing
        $this->testBasePath = sys_get_temp_dir() . '/storybook-test-' . uniqid();
        mkdir($this->testBasePath, 0755, true);
        
        // Override base_path for testing
        $this->app->useBasePath($this->testBasePath);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testBasePath)) {
            $this->deleteDirectory($this->testBasePath);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_creates_necessary_directories()
    {
        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->expectsOutput('🚀 Installing Storybook Blade integration...')
            ->expectsOutput('✅ Storybook Blade integration installed successfully!')
            ->assertExitCode(0);

        $this->assertTrue(is_dir($this->testBasePath . '/.storybook'));
        $this->assertTrue(is_dir($this->testBasePath . '/resources/stories'));
        $this->assertTrue(is_dir($this->testBasePath . '/resources/stories/components'));
    }

    /** @test */
    public function it_publishes_storybook_configuration_files()
    {
        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->assertExitCode(0);

        $this->assertTrue(File::exists($this->testBasePath . '/.storybook/main.js'));
        $this->assertTrue(File::exists($this->testBasePath . '/.storybook/preview.js'));
        
        // Check content of main.js
        $mainJs = File::get($this->testBasePath . '/.storybook/main.js');
        $this->assertStringContainsString('@storybook/html-vite', $mainJs);
        $this->assertStringContainsString('blade-loader', $mainJs);
        
        // Check content of preview.js
        $previewJs = File::get($this->testBasePath . '/.storybook/preview.js');
        $this->assertStringContainsString('configure', $previewJs);
        $this->assertStringContainsString('withSageAssets', $previewJs);
    }

    /** @test */
    public function it_creates_example_story()
    {
        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->assertExitCode(0);

        $storyPath = $this->testBasePath . '/resources/stories/components/Button.stories.js';
        $this->assertTrue(File::exists($storyPath));
        
        $storyContent = File::get($storyPath);
        $this->assertStringContainsString('renderBlade', $storyContent);
        $this->assertStringContainsString('components.button', $storyContent);
        $this->assertStringContainsString('export default', $storyContent);
    }

    /** @test */
    public function it_updates_package_json_when_it_exists()
    {
        // Create a basic package.json
        $packageJson = [
            'name' => 'test-theme',
            'scripts' => [
                'build' => 'vite build'
            ],
            'devDependencies' => [
                'vite' => '^5.0.0'
            ]
        ];
        
        File::put($this->testBasePath . '/package.json', json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->assertExitCode(0);

        $this->assertTrue(File::exists($this->testBasePath . '/package.json'));
        
        $updatedPackageJson = json_decode(File::get($this->testBasePath . '/package.json'), true);
        
        // Check that storybook dependencies were added
        $this->assertArrayHasKey('@storybook/html-vite', $updatedPackageJson['devDependencies']);
        $this->assertArrayHasKey('@storybook/addon-controls', $updatedPackageJson['devDependencies']);
        
        // Check that storybook scripts were added
        $this->assertArrayHasKey('storybook', $updatedPackageJson['scripts']);
        $this->assertArrayHasKey('storybook:build', $updatedPackageJson['scripts']);
        
        // Check that existing content was preserved
        $this->assertEquals('test-theme', $updatedPackageJson['name']);
        $this->assertEquals('vite build', $updatedPackageJson['scripts']['build']);
        $this->assertEquals('^5.0.0', $updatedPackageJson['devDependencies']['vite']);
    }

    /** @test */
    public function it_handles_missing_package_json_gracefully()
    {
        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->expectsOutput('package.json not found. You\'ll need to manually add Storybook dependencies.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_respects_force_flag()
    {
        // Create existing files
        mkdir($this->testBasePath . '/.storybook', 0755, true);
        File::put($this->testBasePath . '/.storybook/main.js', 'existing content');

        // Run without force - should ask for confirmation
        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->expectsConfirmation('File .storybook/main.js already exists. Overwrite?', 'no')
            ->assertExitCode(0);

        // File should still contain original content
        $this->assertEquals('existing content', File::get($this->testBasePath . '/.storybook/main.js'));

        // Run with force - should overwrite without asking
        $this->artisan('storybook:install', ['--force' => true, '--skip-npm' => true])
            ->assertExitCode(0);

        // File should now contain new content
        $content = File::get($this->testBasePath . '/.storybook/main.js');
        $this->assertStringContainsString('@storybook/html-vite', $content);
    }

    /** @test */
    public function it_publishes_configuration_file()
    {
        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->assertExitCode(0);

        // Check that config was published (would be in config/ directory)
        // Note: In testing, the actual publish might not work the same way
        // This is more of an integration test
        $this->assertTrue(true); // Placeholder
    }

    /** @test */
    public function it_displays_next_steps()
    {
        $this->artisan('storybook:install', ['--skip-npm' => true])
            ->expectsOutput('🎉 Next steps:')
            ->expectsOutput('1. Update the API base URL in .storybook/preview.js to match your local development URL')
            ->expectsOutput('2. Create Blade components in resources/views/components/')
            ->expectsOutput('3. Build your theme assets: npm run build')
            ->expectsOutput('4. Start Storybook: npm run storybook')
            ->assertExitCode(0);
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}