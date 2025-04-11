<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\Deprecations\Deprecation;
use SolidInvoice\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv('SOLIDINVOICE_ENV', 'SOLIDINVOICE_DEBUG'))->bootEnv(dirname(__DIR__) . '/.env', 'test');

if (class_exists(Deprecation::class)) {
    Deprecation::enableWithTriggerError();
}

(static function (): void {
    $kernel = new Kernel('test', true);
    $kernel->boot();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    /*$application->run(new ArrayInput([
        'command' => 'doctrine:database:create',
    ]));*/

    $application->run(new ArrayInput([
        'command' => 'doctrine:schema:update',
        '--force' => true,
        '--complete' => true,
        '--quiet' => true,
    ]));

    $kernel->shutdown();
})();
