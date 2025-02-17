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
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\Kernel;
use SolidInvoice\SettingsBundle\SystemConfig;
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

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--if-exists' => true,
        '--force' => true,
    ]));

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:create',
    ]));

    $application->run(new ArrayInput([
        'command' => 'doctrine:migrations:migrate',
        '--allow-no-migration' => true,
        '--no-interaction' => true,
    ]));

    $kernel->getContainer()->get(SystemConfig::class)->set(SystemConfig::CURRENCY_CONFIG_PATH, 'USD');

    /** @var VersionRepository $version */
    $version = $kernel->getContainer()->get('doctrine')->getManager()->getRepository(Version::class);
    $version->updateVersion(SolidInvoiceCoreBundle::VERSION);

    $kernel->shutdown();
})();
