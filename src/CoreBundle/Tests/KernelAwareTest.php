<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../app/AppKernel.php';

abstract class KernelAwareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \AppKernel
     */
    protected $kernel;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    public function setUp()
    {
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();

        parent::setUp();
    }

    public function tearDown()
    {
        $this->kernel->shutdown();

        parent::tearDown();
    }
}
