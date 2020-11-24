<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (!class_exists(Dotenv::class)) {
    throw new LogicException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
}

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (is_array($env = @include dirname(__DIR__).'/.env.local.php') && (!isset($env['SOLIDINVOICE_ENV']) || ($_SERVER['SOLIDINVOICE_ENV'] ?? $_ENV['SOLIDINVOICE_ENV'] ?? $env['SOLIDINVOICE_ENV']) === $env['SOLIDINVOICE_ENV'])) {
    (new Dotenv(false))->populate($env);
} else {
    // load all the .env files
    (new Dotenv(false))->loadEnv(dirname(__DIR__).'/.env');
}

$_SERVER += $_ENV;
$_SERVER['SOLIDINVOICE_ENV'] = $_ENV['SOLIDINVOICE_ENV'] = ($_SERVER['SOLIDINVOICE_ENV'] ?? $_ENV['SOLIDINVOICE_ENV'] ?? null) ?: 'dev';
$_SERVER['SOLIDINVOICE_DEBUG'] = $_SERVER['SOLIDINVOICE_DEBUG'] ?? $_ENV['SOLIDINVOICE_DEBUG'] ?? 'prod' !== $_SERVER['SOLIDINVOICE_ENV'];
$_SERVER['SOLIDINVOICE_DEBUG'] = $_ENV['SOLIDINVOICE_DEBUG'] = (int) $_SERVER['SOLIDINVOICE_DEBUG'] || filter_var($_SERVER['SOLIDINVOICE_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
