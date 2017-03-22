<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle;

use CSBill\CoreBundle\Kernel\ContainerClassKernelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigWriter
{
    /**
     * @var string
     */
    private $configDir;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var ContainerClassKernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $configFile;

    /**
     * @param ContainerClassKernelInterface $kernel
     * @param Filesystem                    $fileSystem
     */
    public function __construct(ContainerClassKernelInterface $kernel, Filesystem $fileSystem)
    {
        $this->configDir = $kernel->getConfigDir();
        $this->fileSystem = $fileSystem;
        $this->kernel = $kernel;
        $this->configFile = $this->configDir.'/parameters.yml';
    }

    /**
     * Dumps an array into the parameters.yml file.
     *
     * @param array $config
     */
    public function dump(array $config)
    {
        $values = [
            'parameters' => array_merge($this->getConfigValues(), $config),
        ];

        $yaml = Yaml::dump($values);

        $this->fileSystem->dumpFile($this->configFile, $yaml);

        $this->fileSystem->remove(
            sprintf(
                '%s/%s.php',
                $this->kernel->getCacheDir(),
                $this->kernel->getContainerCacheClass()
            )
        );
    }

    /**
     * Get all values from the config file.
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    public function getConfigValues(): array
    {
        try {
            $value = Yaml::parse(file_get_contents($this->configFile));
        } catch (ParseException $e) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to parse the YAML string: %s Your installation might be corrupt.',
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        return $value['parameters'];
    }
}
