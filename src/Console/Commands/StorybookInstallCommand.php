<?php

namespace ChrisVasey\SageStorybookBlade\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StorybookInstallCommand extends Command
{
    protected $signature = 'storybook:install 
                            {--force : Overwrite existing files}
                            {--skip-npm : Skip npm package installation}';

    protected $description = 'Install Storybook Blade integration for Sage theme';

    public function handle()
    {
        $this->info('🚀 Installing Storybook Blade integration...');

        // Create necessary directories
        $this->createDirectories();

        // Publish configuration files
        $this->publishFiles();

        // Update package.json
        if (! $this->option('skip-npm')) {
            $this->updatePackageJson();
        }

        // Generate initial story if none exist
        $this->generateInitialStory();

        $this->info('✅ Storybook Blade integration installed successfully!');
        $this->displayNextSteps();
    }

    protected function createDirectories()
    {
        $directories = [
            '.storybook',
            'resources/stories',
            'resources/stories/components',
        ];

        foreach ($directories as $dir) {
            $path = base_path($dir);

            if (! File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->info("✓ Created directory: {$dir}");
            }
        }
    }

    protected function publishFiles()
    {
        $stubsPath = __DIR__.'/../../../resources/stubs';

        $files = [
            'storybook-main.js' => '.storybook/main.js',
            'storybook-preview.js' => '.storybook/preview.js',
        ];

        foreach ($files as $stub => $destination) {
            $stubPath = "{$stubsPath}/{$stub}";
            $destinationPath = base_path($destination);

            if (File::exists($destinationPath) && ! $this->option('force')) {
                if (! $this->confirm("File {$destination} already exists. Overwrite?")) {
                    continue;
                }
            }

            File::copy($stubPath, $destinationPath);
            $this->info("✓ Published: {$destination}");
        }

        // Publish config file
        $this->call('vendor:publish', [
            '--tag' => 'storybook-config',
            '--force' => $this->option('force'),
        ]);
    }

    protected function updatePackageJson()
    {
        $packageJsonPath = base_path('package.json');

        if (! File::exists($packageJsonPath)) {
            $this->warn('package.json not found. You\'ll need to manually add Storybook dependencies.');

            return;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);
        $updatesPath = __DIR__.'/../../../resources/stubs/package-json-updates.json';
        $updates = json_decode(File::get($updatesPath), true);

        // Merge dev dependencies
        $packageJson['devDependencies'] = array_merge(
            $packageJson['devDependencies'] ?? [],
            $updates['devDependencies']
        );

        // Merge scripts
        $packageJson['scripts'] = array_merge(
            $packageJson['scripts'] ?? [],
            $updates['scripts']
        );

        File::put($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('✓ Updated package.json with Storybook dependencies');

        if ($this->confirm('Install npm dependencies now?')) {
            $this->info('Installing npm dependencies...');
            $this->line(''); // Empty line for better output formatting

            exec('npm install', $output, $exitCode);

            if ($exitCode === 0) {
                $this->info('✓ npm dependencies installed successfully');
            } else {
                $this->error('Failed to install npm dependencies. Please run `npm install` manually.');
            }
        }
    }

    protected function generateInitialStory()
    {
        $storyPath = base_path('resources/stories/components/Button.stories.js');

        if (File::exists($storyPath) && ! $this->option('force')) {
            return;
        }

        $stubPath = __DIR__.'/../../../resources/stubs/example-story.js';
        $content = File::get($stubPath);

        // Customize the content based on the current site configuration
        $siteUrl = config('app.url', 'https://your-site.test');
        $content = str_replace('https://your-site.test', $siteUrl, $content);

        File::put($storyPath, $content);
        $this->info('✓ Created example story: resources/stories/components/Button.stories.js');
    }

    protected function displayNextSteps()
    {
        $this->newLine();
        $this->info('🎉 Next steps:');
        $this->line('1. Update the API base URL in .storybook/preview.js to match your local development URL');
        $this->line('2. Create Blade components in resources/views/components/');
        $this->line('3. Build your theme assets: npm run build');
        $this->line('4. Start Storybook: npm run storybook');
        $this->newLine();
        $this->info('📖 Check the documentation for more information about creating stories.');
    }
}
