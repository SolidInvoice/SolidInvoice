<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice;

use Symfony\Requirements\SymfonyRequirements;

/**
 * @codeCoverageIgnore
 */
class AppRequirements extends SymfonyRequirements
{
    public function __construct()
    {
        parent::__construct(dirname(__DIR__), '4.0.0');

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

    public function addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        if ('PDO should be installed' === $testMessage || preg_match('#PDO should have some drivers installed#', $testMessage)) {
            return parent::addRequirement($fulfilled, $testMessage, $helpHtml, $helpText);
        }

        return parent::addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText);
    }
}
