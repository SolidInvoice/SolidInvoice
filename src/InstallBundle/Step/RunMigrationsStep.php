<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Step;

use SolidInvoice\InstallBundle\Installer\Database\Migration;

final class RunMigrationsStep implements InstallationStepInterface
{
    public function __construct(
        private readonly Migration $migration
    ) {
    }

    public static function priority(): int
    {
        return 10;
    }

    public function execute(?callable $callback = null): void
    {
        $this->migration->migrate($callback);
    }

    public static function getLabel(): string
    {
        return 'Creating database schema';
    }
}
