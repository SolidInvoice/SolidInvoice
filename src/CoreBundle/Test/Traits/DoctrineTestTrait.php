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
    public function setupDoctrine(): void
    {
        $kernel = static::bootKernel();

        $this->registry = $kernel->getContainer()->get('doctrine');
        $this->em = $this->registry->getManager();
    }
}
