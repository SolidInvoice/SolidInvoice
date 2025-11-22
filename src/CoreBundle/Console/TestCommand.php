<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Console;

use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

#[AsCommand(name: 'solidinvoice:test', description: 'Test command for SolidInvoice')]
class TestCommand extends Command
{
    protected function handle(): int
    {
        $passwordHasher = new NativePasswordHasher();

        UserFactory::createMany(20000, ['password' => $passwordHasher->hash('password123')]);

        return self::SUCCESS;
    }
}
