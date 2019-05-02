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

namespace SolidInvoice\CoreBundle\Test\Listener;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

class DoctrineSetupListener implements TestListener
{
    use TestListenerDefaultImplementation;

    public function startTest(Test $test): void
    {
        if (method_exists($test, 'setupDoctrine')) {
            $test->setupDoctrine();
        }
    }
}