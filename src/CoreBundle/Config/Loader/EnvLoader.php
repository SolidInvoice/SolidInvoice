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
use function sprintf;

final class EnvLoader implements EnvVarLoaderInterface
{
    /**
     * @var array<string, string>
     */
    private static array $driverSchemeAliases = [
        'ibm_db2' => 'db2',
        'pdo_sqlsrv' => 'mssql',
        'pdo_mysql' => 'mysql',
        'pdo_pgsql' => 'postgres',
        'pdo_sqlite' => 'sqlite3',
    ];

    private string $projectDir;

    private Filesystem $fileSystem;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->fileSystem = new Filesystem();
    }

    /**
     * @return array<string, string>
     */
    public function loadEnvVars(): array
    {
        $fileName = 'env.php';

        $newEnvPath = $this->projectDir . '/config/env';

        if ($this->fileSystem->exists("{$newEnvPath}/{$fileName}")) {
            return $this->loadEnv(require "{$newEnvPath}/{$fileName}");
        }

        $oldEnvFile = $this->projectDir . '/config/env.php';

        if ($this->fileSystem->exists($oldEnvFile)) {
            $this->fileSystem->mkdir($newEnvPath);
            $this->fileSystem->rename($oldEnvFile, "{$newEnvPath}/{$fileName}");

            return $this->loadEnv(require "{$newEnvPath}/{$fileName}");
        }

        return [];
    }

    /**
     * @param array<string, string> $param
     * @return array<string, string>
     */
    private function loadEnv(array $param): array
    {
        if (isset($param['database_host'])) {
            $param['DATABASE_URL'] = sprintf(
                '%s://%s%s%s%s%s%s?serverVersion=%s',
                self::$driverSchemeAliases[$param['database_driver']] ?? $param['database_driver'],
                $param['database_user'] ?? '',
                isset($param['database_password']) ? ':' . $param['database_password'] : '',
                isset($param['database_user']) ? '@' : '',
                $param['database_host'],
                isset($param['database_port']) ? ':' . $param['database_port'] : '',
                isset($param['database_name']) ? '/' . $param['database_name'] : '',
                $param['database_version'] ?? ''
            );

            unset(
                $param['database_host'],
                $param['database_port'],
                $param['database_name'],
                $param['database_user'],
                $param['database_password'],
                $param['database_driver'],
                $param['database_version']
            );
        }

        return $param;
    }
}
