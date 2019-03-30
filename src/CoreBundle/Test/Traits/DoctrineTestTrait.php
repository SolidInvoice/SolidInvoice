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

namespace SolidInvoice\CoreBundle\Test\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mockery as M;
use Mockery\MockInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @codeCoverageIgnore
 */
trait DoctrineTestTrait
{
    /**
     * @var ManagerRegistry|MockInterface
     */
    protected $registry;

    /**
     * @var EntityManager|MockInterface
     */
    protected $em;

    /**
     * @before
     */
    protected function setupDoctrine()
    {
        $this->em = M::mock(EntityManagerInterface::class);
        $this->createRegistryMock('default', $this->em);
    }

    protected function createRegistryMock($name, $em)
    {
        $this->registry = M::mock(ManagerRegistry::class);
        $this->registry->shouldReceive('getManager')
            ->zeroOrMoreTimes()
            ->with()
            ->andReturn($em);

        $this->registry->shouldReceive('getManager')
            ->zeroOrMoreTimes()
            ->with($name)
            ->andReturn($em);

        $this->registry->shouldReceive('getManagers')
            ->with()
            ->andReturn(['default' => $em]);

        $this->registry->shouldReceive('getManagerForClass')
            ->andReturn($em);

        return $this->registry;
    }
}
