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
use Doctrine\Persistence\ManagerRegistry;
use Mockery\MockInterface;

/**
 * @codeCoverageIgnore
 */
trait DoctrineTestTrait
{
    use SymfonyKernelTrait;

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
    public function setupDoctrine()
    {
        $this->setUpSymfonyKernel();

        $this->registry = $this->container->get('doctrine');
        $this->em = $this->registry->getManager();
    }
}
