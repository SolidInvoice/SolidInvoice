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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use DoctrineMigrations\Version30100_7;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * `CreditNoteMigrationTest` proves the migration's `Schema` mutation is internally consistent
 * and preserves rows on SQLite. It does not catch the migration disagreeing with the real ORM
 * mapping about a column name — that is exactly what let `CreditNoteLine::$creditNote`'s missing
 * `JoinColumn` `name:` (deriving `creditNote_id` instead of `credit_note_id`) through review.
 *
 * This test builds the "desired" schema from the actual, current entity metadata — not a
 * hand-written fixture that could quietly assume the same wrong name the migration uses — then
 * proves `down()` followed by `up()`/`postUp()` round-trips back to exactly that, column names,
 * indexes and all.
 *
 * @see Version30100_7
 */
final class CreditNoteMigrationSchemaSyncTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function testDownThenUpRoundTripsToExactlyWhatTheMappingWants(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        $desiredSchema = new SchemaTool($em)->getSchemaFromMetadata($em->getMetadataFactory()->getAllMetadata());

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $platform = $connection->getDatabasePlatform();

        foreach ($desiredSchema->toSql($platform) as $sql) {
            $connection->executeStatement($sql);
        }

        // Ground truth: what the mapping wants, materialised for real rather than asserted
        // against in the abstract — a `Schema` object never disagrees with itself.
        $groundTruth = $connection->createSchemaManager()->introspectSchema();

        $migration = new Version30100_7($connection, new NullLogger());

        // One migration back...
        $beforeDown = $connection->createSchemaManager()->introspectSchema();
        $afterDown = clone $beforeDown;
        $migration->preDown($afterDown);
        $migration->down($afterDown);
        $this->executeDiff($connection, $beforeDown, $afterDown);

        // ...and forward again.
        $beforeUp = $connection->createSchemaManager()->introspectSchema();
        $afterUp = clone $beforeUp;
        $migration->up($afterUp);
        $this->executeDiff($connection, $beforeUp, $afterUp);
        $migration->postUp($afterUp);

        $roundTripped = $connection->createSchemaManager()->introspectSchema();

        $diff = $connection->createSchemaManager()->createComparator()->compareSchemas($groundTruth, $roundTripped);

        self::assertTrue(
            $diff->isEmpty(),
            'Migrating down then up again did not reproduce the schema the entity mapping wants — '
            . 'the migration and the mapping have drifted apart.'
        );
    }

    /**
     * Mirrors what the Doctrine Migrations runner does: diff the schema a migration mutated
     * against the schema as it was before, then execute the platform SQL the diff produces.
     *
     * @throws Exception
     */
    private function executeDiff(Connection $connection, Schema $fromSchema, Schema $toSchema): void
    {
        $platform = $connection->getDatabasePlatform();
        $diff = $connection->createSchemaManager()->createComparator()->compareSchemas($fromSchema, $toSchema);

        foreach ($platform->getAlterSchemaSQL($diff) as $sql) {
            $connection->executeStatement($sql);
        }
    }
}
