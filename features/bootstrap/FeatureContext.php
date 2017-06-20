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

use Behat\Behat\Context\Context;
use CSBill\InstallBundle\Installer\Database\Migration;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * @var Migration
     */
    private $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * @BeforeScenario
     */
    public function migrateDatabase()
    {
        $this->migration->migrate();
    }
}