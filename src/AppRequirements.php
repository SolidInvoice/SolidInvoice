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

use const PHP_VERSION;
use const PHP_VERSION_ID;
use Symfony\Requirements\SymfonyRequirements;
use function sprintf;

/**
 * @codeCoverageIgnore
 */
class AppRequirements extends SymfonyRequirements
{
    public function __construct()
    {
        $this->addRequirement(
            PHP_VERSION_ID >= 80200,
            sprintf('PHP version must be at least %s (%s installed)', '8.2.0', PHP_VERSION),
            sprintf(
                'You are running PHP version "<strong>%s</strong>", but SolidInvoice needs at least PHP "<strong>%s</strong>" to run.
            Before using SolidInvoice, upgrade your PHP installation, preferably to the latest version.',
                PHP_VERSION,
                '8.2.0',
            ),
            sprintf('Install PHP %s or newer (installed version is %s)', '7.4.15', PHP_VERSION)
        );

        parent::__construct();

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

    public function addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText = null): void
    {
        if ('PDO should be installed' === $testMessage || preg_match('#PDO should have some drivers installed#', $testMessage)) {
            $this->addRequirement($fulfilled, $testMessage, $helpHtml, $helpText);
            return;
        }

        parent::addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText);
    }
}
