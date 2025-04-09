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
use SolidInvoice\InstallBundle\Config\DatabaseConfig;
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
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function migrateToDatabaseUrl(array $params): array
    {
        if (isset($params['database_host'])) {
            $params['database_url'] = DatabaseConfig::paramsToDatabaseUrl($params);

            unset(
                $params['database_host'],
                $params['database_port'],
                $params['database_name'],
                $params['database_user'],
                $params['database_password'],
                $params['database_driver'],
                $params['database_version']
            );
        }

        return $params;
    }

    /**
     * @return array<string, string>
     */
    private function migrateToSecrets(string $path): array
    {
        $values = $this->migrateToDatabaseUrl(require $path);

        $env = [];

        foreach ($values as $key => $value) {
            if ($key === 'secret') {
                $key = 'app_secret';
            }

            $env[strtoupper($key)] = $value;
        }

        $this->configWriter->save($env);

        $this->fileSystem->remove($path);

        return $env;
    }
}
