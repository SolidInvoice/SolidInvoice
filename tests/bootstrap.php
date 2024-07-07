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

require dirname(__DIR__) . '/vendor/autoload.php';

if (class_exists(Deprecation::class)) {
    Deprecation::enableWithTriggerError();
}

if (false === (bool) $_SERVER['SOLIDINVOICE_DEBUG']) {
    // ensure fresh cache
    (new Symfony\Component\Filesystem\Filesystem())->remove(__DIR__ . '/../var/cache/test');
}

/*
(static function (): void {
    $kernel = new Kernel('test', true);
    $kernel->boot();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--if-exists' => '1',
        '--force' => '1',
    ]));

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:create',
    ]));

    $application->run(new ArrayInput([
        'command' => 'doctrine:migrations:migrate',
        '--allow-no-migration' => '1',
        '--no-interaction' => '1',
    ]));

    $kernel->getContainer()->get(SystemConfig::class)->set(SystemConfig::CURRENCY_CONFIG_PATH, 'USD');

    /** @var VersionRepository $version * /
    $version = $kernel->getContainer()->get('doctrine')->getManager()->getRepository(Version::class);
    $version->updateVersion(SolidInvoiceCoreBundle::VERSION);

    $kernel->shutdown();
})();*/
