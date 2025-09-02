<?php

require_once __DIR__.'/../vendor/autoload.php';

// Set testing environment variables
$_ENV['APP_ENV'] = 'testing';
$_ENV['STORYBOOK_ENABLED'] = 'true';
$_ENV['STORYBOOK_REQUIRE_DEBUG'] = 'false';
