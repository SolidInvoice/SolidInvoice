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
use Symfony\Component\Filesystem\Filesystem;

final class EnvLoader implements EnvVarLoaderInterface
{
    private string $projectDir;

    private Filesystem $fileSystem;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->fileSystem = new Filesystem();
    }

    public function loadEnvVars(): array
    {
        $fileName = 'env.php';

        $newEnvPath = $this->projectDir . '/config/env';

        if ($this->fileSystem->exists("{$newEnvPath}/{$fileName}")) {
            return require "{$newEnvPath}/{$fileName}";
        }

        $oldEnvFile = $this->projectDir . '/config/env.php';

        if ($this->fileSystem->exists($oldEnvFile)) {
            $this->fileSystem->mkdir($newEnvPath);
            $this->fileSystem->rename($oldEnvFile, "{$newEnvPath}/{$fileName}");

            return require "{$newEnvPath}/{$fileName}";
        }

        return [];
    }
}
