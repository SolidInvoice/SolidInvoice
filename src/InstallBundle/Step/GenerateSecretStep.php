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

use Defuse\Crypto\Key;
use SolidInvoice\CoreBundle\ConfigWriter;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;

final class GenerateSecretStep implements InstallationStepInterface
{
    public function __construct(
        private readonly AbstractVault $vault,
        private readonly ConfigWriter $configWriter,
    ) {
    }

    public static function priority(): int
    {
        return 30;
    }

    public function execute(?callable $callback = null): void
    {
        $this->vault->generateKeys();
        $this->configWriter->save([
            'APP_SECRET' => Key::createNewRandomKey()->saveToAsciiSafeString(),
        ]);
        $callback($this->vault->getLastMessage());
    }

    public static function getLabel(): string
    {
        return 'Generating secret';
    }
}
