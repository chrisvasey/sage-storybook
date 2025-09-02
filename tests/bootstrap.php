<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Orchestra\Testbench\Foundation\Bootstrap\LoadEnvironmentVariables;

// Load environment variables
(new LoadEnvironmentVariables())->bootstrap();

// Set testing environment variables
$_ENV['APP_ENV'] = 'testing';
$_ENV['STORYBOOK_ENABLED'] = 'true';
$_ENV['STORYBOOK_REQUIRE_DEBUG'] = 'false';