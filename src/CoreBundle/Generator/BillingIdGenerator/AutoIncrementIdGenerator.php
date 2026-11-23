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
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use function assert;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Generator\BillingIdGenerator\AutoIncrementIdGeneratorTest
 */
#[AsTaggedItem('auto_increment')]
final readonly class AutoIncrementIdGenerator implements IdGeneratorInterface
{
    public function __construct(
        private ManagerRegistry $registry
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

        $filters->disable('archivable');

        try {
            $rawField = 'e.' . $options['field'];
            $field = $rawField;
            $prefix = $options['prefix'] ?? '';
            $suffix = $options['suffix'] ?? '';
            $prefixLength = strlen((string) $prefix);
            $suffixLength = strlen($suffix);

            $qb = $this->registry
                ->getRepository($entity::class)
                ->createQueryBuilder('e');

            if ($prefixLength > 0 || $suffixLength > 0) {
                $field = sprintf(
                    'SUBSTRING(%s, %d, LENGTH(%s) - %d)',
                    $rawField,
                    $prefixLength + 1,
                    $rawField,
                    $prefixLength + $suffixLength
                );

                // A value shorter than prefix+suffix (an id not assigned yet, still
                // its default '') makes the SUBSTRING length negative. MySQL/MariaDB/
                // SQLite tolerate that, but PostgreSQL raises "negative substring
                // length not allowed". Exclude those rows instead of relying on
                // platform-specific clamping; they are not numbered entries anyway.
                $qb->andWhere(sprintf('LENGTH(%s) >= :minLength', $rawField))
                    ->setParameter('minLength', $prefixLength + $suffixLength);
            }

            $lastId = $qb
                ->select(sprintf('MAX(ABS(TO_NUMBER(%s)))', $field))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException) {
            $lastId = 0;
        } finally {
            $filters->enable('archivable');
        }

        return (string) ($lastId + 1);
    }
}
