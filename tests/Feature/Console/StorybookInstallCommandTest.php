<?php

namespace ChrisVasey\SageStorybookBlade\Tests\Feature\Console;

use ChrisVasey\SageStorybookBlade\Tests\TestCase\PackageTestCase;

class StorybookInstallCommandTest extends PackageTestCase
{
    /** @test */
    public function install_command_is_available()
    {
        // Check that command exists by looking for it in kernel
        $kernel = app(\Illuminate\Contracts\Console\Kernel::class);
        $commands = $kernel->all();

        $this->assertArrayHasKey('storybook:install', $commands);
    }

    /** @test */
    public function install_command_can_be_called()
    {
        // Test that command shows help without errors
        $this->artisan('storybook:install', ['--help'])
            ->assertExitCode(0);
    }
}
