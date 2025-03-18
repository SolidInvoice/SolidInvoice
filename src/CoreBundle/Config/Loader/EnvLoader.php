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

use SolidInvoice\CoreBundle\ConfigWriter;
use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use function strtoupper;

final class EnvLoader implements EnvVarLoaderInterface
{
    private Filesystem $fileSystem;

    public function __construct(
        private readonly string $projectDir,
        private readonly ConfigWriter $configWriter,
    ) {
        $this->fileSystem = new Filesystem();
    }

    public function loadEnvVars(): array
    {
        $fileName = 'env.php';

        $newEnvPath = $this->projectDir . '/config/env';

        if ($this->fileSystem->exists("{$newEnvPath}/{$fileName}")) {
            return $this->migrateToSecrets("{$newEnvPath}/{$fileName}");
        }

        $oldEnvFile = $this->projectDir . '/config/' . $fileName;

        if ($this->fileSystem->exists($oldEnvFile)) {
            return $this->migrateToSecrets($oldEnvFile);
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function migrateToSecrets(string $path): array
    {
        $values = require $path;

        $this->configWriter->save($values);

        $this->fileSystem->remove($path);

        $env = [];

        foreach ($values as $key => $value) {
            if (! str_starts_with($key, ConfigWriter::CONFIG_PREFIX)) {
                $key = ConfigWriter::CONFIG_PREFIX . $key;
            }

            $env[strtoupper($key)] = $value;
        }

        return $env;
    }
}
