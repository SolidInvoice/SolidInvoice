<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require_once __DIR__.'/SymfonyRequirements.php';

use Symfony\Component\Intl\Intl;

class AppRequirements extends SymfonyRequirements
{
    const REQUIRED_PHP_VERSION = '5.4.11';
    const REQUIRED_ICU_VERSION = '3.8';
    const EXCLUDE_REQUIREMENTS_MASK = '/5\.3\.(3|4|8|16)|5\.4\.(0|8)|(logout handler)/';
    const EXCLUDE_RECOMMENDED_MASK = '/5\.3\.(3|4|8|16)|5\.4\.(0|8)|(logout handler|PDO)/';

    public function __construct()
    {
        $phpVersion = phpversion();
        $icuVersion = Intl::getIcuVersion();

        $this->addRequirement(
            version_compare($phpVersion, self::REQUIRED_PHP_VERSION, '>='),
            sprintf('PHP version must be at least %s (%s installed)', self::REQUIRED_PHP_VERSION, $phpVersion),
            sprintf('You are running PHP version "<strong>%s</strong>", but CSBill needs at least PHP "<strong>%s</strong>" to run.
                Before using CSBill, upgrade your PHP installation, preferably to the latest version.',
                $phpVersion, self::REQUIRED_PHP_VERSION),
            sprintf('Install PHP %s or newer (installed version is %s)', self::REQUIRED_PHP_VERSION, $phpVersion)
        );

        parent::__construct();

        $this->addRequirement(
            function_exists('mcrypt_encrypt'),
            'mcrypt_encrypt() should be available',
            'Install and enable the <strong>Mcrypt</strong> extension.'
        );

        $this->addRequirement(
            class_exists('Locale'),
            'intl extension should be available',
            'Install and enable the <strong>intl</strong> extension.'
        );

        $this->addRequirement(
            null !== $icuVersion && version_compare($icuVersion, self::REQUIRED_ICU_VERSION, '>='),
            'icu library must be at least '.self::REQUIRED_ICU_VERSION,
            'Install and enable the <strong>icu</strong> library at least '.self::REQUIRED_ICU_VERSION.' version'
        );

        $baseDir = realpath(__DIR__.'/..');

        $this->addRequirement(
            is_writable($baseDir.'/web/uploads'),
            'web/uploads/ directory must be writable',
            'Change the permissions of the "<strong>web/uploads/</strong>" directory so that the web server can write into it.'
        );

        if (is_file($baseDir.'/app/config/parameters.yml')) {
            $this->addRequirement(
                is_writable($baseDir.'/app/config/parameters.yml'),
                'app/config/parameters.yml file must be writable',
                'Change the permissions of the "<strong>app/config/parameters.yml</strong>" file so that the web server can write into it.'
            );
        }

        $this->addRequirement(
            class_exists('PDO'),
            'PDO should be installed',
            'Install <strong>PDO</strong>.'
        );

        if (class_exists('PDO')) {
            $drivers = PDO::getAvailableDrivers();

            $this->addRequirement(
                in_array('mysql', $drivers),
                sprintf('The MySQL driver for PDO should be installed (currently available: %s)', count($drivers) ? implode(', ', $drivers) : 'none'),
                'Install the <strong>MySQL PDO drivers</strong>.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        $requirements = parent::getRequirements();

        foreach ($requirements as $key => $requirement) {
            $testMessage = $requirement->getTestMessage();
            if (preg_match_all(self::EXCLUDE_REQUIREMENTS_MASK, $testMessage, $matches)) {
                unset($requirements[$key]);
            }
        }

        return $requirements;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendations()
    {
        $recommendations = parent::getRecommendations();

        foreach ($recommendations as $key => $recommendation) {
            $testMessage = $recommendation->getTestMessage();
            if (preg_match_all(self::EXCLUDE_RECOMMENDED_MASK, $testMessage, $matches)) {
                unset($recommendations[$key]);
            }
        }

        return $recommendations;
    }
}
