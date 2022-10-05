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

namespace SolidInvoice;

use Symfony\Requirements\SymfonyRequirements;
use function phpversion;
use function sprintf;
use function str_starts_with;
use function version_compare;

/**
 * @codeCoverageIgnore
 */
class AppRequirements extends SymfonyRequirements
{
    public function __construct()
    {
        parent::__construct(dirname(__DIR__), '5.0.0');

        $this->addRequirement(
            extension_loaded('openssl'),
            'openssl must be loaded',
            'Install and enable the <strong>Openssl</strong> extension.'
        );

        $this->addRecommendation(
            extension_loaded('mbstring'),
            'mbstring extension is required to generate PDF invoices and quotes',
            'Install the PHP mbstring extension'
        );

        $this->addRecommendation(
            extension_loaded('gd'),
            'GD extension is required to generate PDF invoices and quotes',
            'Install the PHP GD extension'
        );
    }

    public function addRequirement($fulfilled, $testMessage, $helpHtml, $helpText = null): void
    {
        if (str_starts_with($testMessage, 'PHP version must be at least ')) {
            $installedPhpVersion = phpversion();
            $phpVersion = '7.4.15';

            parent::addRequirement(
                version_compare($installedPhpVersion, $phpVersion, '>='),
                sprintf('PHP version must be at least %s (%s installed)', $phpVersion, $installedPhpVersion),
                sprintf('You are running PHP version "<strong>%s</strong>", but SolidInvoice needs at least PHP "<strong>%s</strong>" to run.
            Before using SolidInvoice, upgrade your PHP installation, preferably to the latest version.',
                    $installedPhpVersion, $phpVersion),
                sprintf('Install PHP %s or newer (installed version is %s)', $phpVersion, $installedPhpVersion)
            );
            return;
        }

        parent::addRequirement($fulfilled, $testMessage, $helpHtml, $helpText);
    }


    public function addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText = null): void
    {
        if ('PDO should be installed' === $testMessage || preg_match('#PDO should have some drivers installed#', $testMessage)) {
            parent::addRequirement($fulfilled, $testMessage, $helpHtml, $helpText);
            return;
        }

        parent::addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText);
    }
}
