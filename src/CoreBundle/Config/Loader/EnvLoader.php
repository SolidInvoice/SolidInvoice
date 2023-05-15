<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Config\Loader;

use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;

final class EnvLoader implements EnvVarLoaderInterface
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function loadEnvVars(): array
    {
        $envFile = $this->projectDir . '/config/env.php';

        if (! \file_exists($envFile)) {
            return [];
        }

        return require $envFile;
    }
}
