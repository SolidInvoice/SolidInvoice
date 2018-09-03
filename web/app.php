<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

/*
 * @var Composer\Autoload\ClassLoader
 */
$loader = require __DIR__.'/../app/autoload.php';
include_once __DIR__.'/../var/bootstrap.php.cache';

if (!getenv('SOLIDINVOICE_ENV') && file_exists($file = dirname(__DIR__).'/.env')) {
    (new Dotenv())->load($file);
}

if (!getenv('SOLIDINVOICE_ENV')) {
    throw new \RuntimeException('Environment is not set up correctly. "SOLIDINVOICE_ENV" environment variable is missing.');
}

$debug = (bool) getenv('SOLIDINVOICE_DEBUG');

if ($debug) {
    Debug::enable();
}

$kernel = new AppKernel(getenv('SOLIDINVOICE_ENV'), $debug);
//$kernel = new AppCache($kernel);
// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
