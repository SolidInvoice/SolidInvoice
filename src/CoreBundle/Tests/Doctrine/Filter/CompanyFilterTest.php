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

namespace SolidInvoice\CoreBundle\Tests\Doctrine\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\UserBundle\Entity\User;

#[CoversClass(CompanyFilter::class)]
final class CompanyFilterTest extends TestCase
{
    private const string COMPANY_ID = '01967E2E2DA3C8730FF7F658B76C209F';

    /**
     * @return iterable<string, array{AbstractPlatform}>
     */
    public static function indexablePlatformProvider(): iterable
    {
        // MariaDBPlatform and MySQLPlatform both extend AbstractMySQLPlatform.
        yield 'mysql' => [new MySQLPlatform()];
        yield 'mariadb' => [new MariaDBPlatform()];
    }

    /**
     * Wrapping the column in HEX() makes the comparison non-sargable, which disqualifies the index on
     * company_id and turns every tenant-scoped query into a full table scan. The literal gets decoded
     * instead, so the column stays bare and indexable.
     */
    #[DataProvider('indexablePlatformProvider')]
    public function testCompanyColumnIsNotWrappedInAFunctionOnMySql(AbstractPlatform $platform): void
    {
        $constraint = $this->filterFor($platform)->addFilterConstraint($this->invoiceMetadata(), 'i0_');

        self::assertSame(sprintf("i0_.company_id = UNHEX('%s')", self::COMPANY_ID), $constraint);
        self::assertStringNotContainsString('HEX(i0_.company_id)', $constraint);
    }

    #[DataProvider('indexablePlatformProvider')]
    public function testUserIdColumnIsNotWrappedInAFunctionOnMySql(AbstractPlatform $platform): void
    {
        $constraint = $this->filterFor($platform)->addFilterConstraint($this->userMetadata(), 'u0_');

        self::assertSame(
            sprintf(
                "u0_.id IN (SELECT user_id FROM user_company WHERE company_id = UNHEX('%s'))",
                self::COMPANY_ID
            ),
            $constraint
        );
    }

    /**
     * Postgres stores the id natively and compares against the RFC 4122 representation.
     */
public function testPostgresComparesTheColumnDirectly(): void
{
    $filter = new CompanyFilter($this->entityManager(new PostgreSQLPlatform()));
    $filter->setParameter('companyId', '01967e2e-2da3-c873-0ff7-f658b76c209f', 'string');

    $constraint = $filter->addFilterConstraint($this->invoiceMetadata(), 'i0_');

    self::assertSame("i0_.company_id = '01967e2e-2da3-c873-0ff7-f658b76c209f'", $constraint);
}

    /**
     * SQLite gained unhex() only in 3.41, so it keeps encoding the column instead.
     */
    public function testSqliteFallsBackToEncodingTheColumn(): void
    {
        $constraint = $this->filterFor(new SQLitePlatform())->addFilterConstraint($this->invoiceMetadata(), 'i0_');

        self::assertSame(sprintf("HEX(i0_.company_id) = '%s'", self::COMPANY_ID), $constraint);
    }

    public function testEntitiesWithoutACompanyAssociationAreNotFiltered(): void
    {
        $metadata = new ClassMetadata(Company::class);
        $metadata->initializeReflection(new RuntimeReflectionService());

        self::assertSame('', $this->filterFor(new MySQLPlatform())->addFilterConstraint($metadata, 'c0_'));
    }

    public function testNoConstraintIsAddedWithoutACompanyParameter(): void
    {
        $filter = new CompanyFilter($this->entityManager(new MySQLPlatform()));

        self::assertSame('', $filter->addFilterConstraint($this->invoiceMetadata(), 'i0_'));
    }

    private function filterFor(AbstractPlatform $platform): CompanyFilter
    {
        $filter = new CompanyFilter($this->entityManager($platform));
        $filter->setParameter('companyId', self::COMPANY_ID, 'string');

        return $filter;
    }

    private function entityManager(AbstractPlatform $platform): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quote')->willReturnCallback(static fn (string $value): string => "'" . $value . "'");
        $connection->method('createQueryBuilder')->willReturnCallback(static fn (): QueryBuilder => new QueryBuilder($connection));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        return $entityManager;
    }

    /**
     * @return ClassMetadata<Invoice>
     */
    private function invoiceMetadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(Invoice::class);
        $metadata->initializeReflection(new RuntimeReflectionService());
        $metadata->mapManyToOne(['fieldName' => 'company', 'targetEntity' => Company::class]);

        return $metadata;
    }

    /**
     * @return ClassMetadata<User>
     */
    private function userMetadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(User::class);
        $metadata->initializeReflection(new RuntimeReflectionService());

        return $metadata;
    }
}
