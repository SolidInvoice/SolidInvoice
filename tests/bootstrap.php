<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


use SolidInvoice\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

require_once __DIR__.'/../config/bootstrap.php';

function bootstrap(): void
{
    $kernelClass = $_SERVER['KERNEL_CLASS'] ?? Kernel::class;

    $kernel = new $kernelClass($_SERVER['SOLIDINVOICE_ENV'] ?? 'test', (bool) ($_SERVER['SOLIDINVOICE_ENV'] ?? false));
    $kernel->boot();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--if-exists' => '1',
        '--force' => '1',
    ]));

    $application->run(new ArrayInput(['command' => 'doctrine:database:create']));
    $application->run(new ArrayInput(['command' => 'doctrine:migrations:migrate', '-n', '-q']));

    $kernel->shutdown();
}

if (false === (bool) ($_SERVER['SKIP_FUNCTIONAL_BOOTSTRAP'] ?? false)) {
    bootstrap();
}
