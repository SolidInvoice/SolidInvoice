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

namespace SolidInvoice\CoreBundle\Generator\BillingIdGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use function assert;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Generator\BillingIdGenerator\AutoIncrementIdGeneratorTest
 */
final class AutoIncrementIdGenerator implements IdGeneratorInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public static function getName(): string
    {
        return 'auto_increment';
    }

    public function getConfigurationFormType(): ?string
    {
        return null;
    }

    public function generate(object $entity, array $options): string
    {
        $em = $this->registry->getManagerForClass($entity::class);
        assert($em instanceof EntityManager);

        $filters = $em->getFilters();
        assert($filters instanceof FilterCollection);

        $filters->disable('archivable');

        try {
            $lastId = $this->registry
                ->getRepository($entity::class)
                ->createQueryBuilder('e')
                ->select('MAX(ABS(e.' . $options['field'] . '))')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException|NoResultException) {
            $lastId = 0;
        } finally {
            $filters->enable('archivable');
        }

        return (string) ($lastId + 1);
    }
}
