<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle;

use CSBill\CoreBundle\Kernel\ContainerClassKernelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigWriter
{
    /**
     * @var string
     */
    private $rootDir;

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
     * @param string                        $rootDir
     * @param ContainerClassKernelInterface $kernel
     * @param Filesystem                    $fileSystem
     */
    public function __construct($rootDir, ContainerClassKernelInterface $kernel, Filesystem $fileSystem)
    {
        $this->rootDir = $rootDir;
        $this->fileSystem = $fileSystem;
        $this->kernel = $kernel;
        $this->configFile = $this->rootDir.'/config/parameters.yml';
    }

    /**
     * Dumps an array into the parameters.yml file
     *
     * @param array $config
     */
    public function dump(array $config, $mode = 0777)
    {
        $values = array(
            'parameters' => array_merge($this->getConfigValues(), $config),
        );

        $yaml = Yaml::dump($values);

        $this->fileSystem->dumpFile($this->configFile, $yaml, $mode);

        $this->fileSystem->remove(
            sprintf(
                '%s/%s.php',
                $this->kernel->getCacheDir(),
                $this->kernel->getContainerCacheClass()
            )
        );
    }

    /**
     * Get all values from the config file
     *
     * @return array
     */
    public function getConfigValues()
    {
        try {
            $value = Yaml::parse(file_get_contents($this->configFile));
        } catch (ParseException $e) {
            throw new \RuntimeException(
                sprintf(
                    "Unable to parse the YAML string: %s Your installation might be corrupt.",
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        return $value['parameters'];
    }
}
