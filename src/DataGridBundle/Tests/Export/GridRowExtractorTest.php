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

namespace SolidInvoice\DataGridBundle\Tests\Export;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\Export\GridRowExtractor;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Uid\Ulid;

#[CoversClass(GridRowExtractor::class)]
final class GridRowExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRowPrependsEntityIdAsBase58(): void
    {
        $ulid = new Ulid();
        $entity = new class($ulid) {
            public function __construct(
                public Ulid $id
            ) {
            }

            public function getName(): string
            {
                return 'Acme';
            }
        };

        $row = $this->makeExtractor($entity, [])->extract(
            [StringColumn::new('name')],
            $entity,
        );

        self::assertSame(['id', 'name'], array_keys($row));
        self::assertSame($ulid->toBase58(), $row['id']);
        self::assertSame('Acme', $row['name']);
    }

    public function testEntityIdIsPresentEvenWhenGridHasNoIdColumn(): void
    {
        $ulid = new Ulid();
        $entity = new class($ulid) {
            public function __construct(
                public Ulid $id
            ) {
            }
        };

        $row = $this->makeExtractor($entity, [])->extract([], $entity);

        self::assertArrayHasKey('id', $row);
        self::assertSame($ulid->toBase58(), $row['id']);
    }

    /**
     * @param list<string> $associations
     */
    private function makeExtractor(object $entity, array $associations): GridRowExtractor
    {
        $metadata = M::mock(ClassMetadata::class);
        $metadata->shouldReceive('getIdentifierValues')
            ->with($entity)
            ->andReturn(['id' => $entity->id]);
        $metadata->shouldReceive('hasAssociation')
            ->andReturnUsing(static fn (string $name): bool => in_array($name, $associations, true));

        $manager = M::mock(ObjectManager::class);
        $manager->shouldReceive('getClassMetadata')
            ->with($entity::class)
            ->andReturn($metadata);

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManagerForClass')
            ->with($entity::class)
            ->andReturn($manager);

        return new GridRowExtractor(
            PropertyAccess::createPropertyAccessor(),
            $registry,
        );
    }
}
