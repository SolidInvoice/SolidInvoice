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

namespace SolidInvoice\CoreBundle;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class ConfigWriter
{
    private readonly Filesystem $fileSystem;

    private readonly string $configFile;

    public function __construct(string $projectDir)
    {
        $this->fileSystem = new Filesystem();
        $this->configFile = $projectDir . '/config/env/env.php';
    }

    /**
     * Dumps an array into the env config file.
     *
     * @param array<string, mixed> $config
     */
    public function dump(array $config): void
    {
        $values = array_merge($this->getConfigValues(), $config);

        $code = "<?php\n\nreturn " . var_export($values, true) . ";\n";

        $this->fileSystem->dumpFile($this->configFile, $code);
    }

    /**
     * Get all values from the config file.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function getConfigValues(): array
    {
        if (! \file_exists($this->configFile)) {
            return [];
        }

        return require $this->configFile;
    }
}
