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

namespace SolidInvoice\PaymentBundle\Tests\Manager;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateMalformedStringException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Money\Currency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\PaymentBundle\Manager\PaymentStats;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;

/** @covers \SolidInvoice\PaymentBundle\Manager\PaymentStats */
final class PaymentStatsTest extends TestCase
{
    /**
     * @var PaymentRepository&MockObject
     */
    private PaymentRepository $repository;

    private PaymentStats $stats;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PaymentRepository::class);
        $this->stats = new PaymentStats($this->repository);
    }

    /**
     * @throws MathException
     * @throws DateMalformedStringException
     */
    public function testGetStatistics(): void
    {
        $totalIncome = ['USD' => BigInteger::of(100000)];
        $thisMonthIncome = ['USD' => BigInteger::of(25000)];
        $recentPayments = [
            ['id' => '1', 'amount' => BigInteger::of(5000)],
            ['id' => '2', 'amount' => BigInteger::of(7500)],
        ];

        $this->repository->expects($this->once())
            ->method('getTotalIncome')
            ->willReturn($totalIncome);

        $this->repository->expects($this->once())
            ->method('getPaymentsThisMonth')
            ->willReturn($thisMonthIncome);

        $this->repository->expects($this->once())
            ->method('getRecentPayments')
            ->with(5)
            ->willReturn($recentPayments);

        // Mock query builder for count queries
        $this->repository->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturnOnConsecutiveCalls(
                $this->mockQueryBuilderForCount(15),  // total_count
                $this->mockQueryBuilderForCount(8),   // this_month_count
                $this->mockQueryBuilderForCount(3),   // pending_count
                $this->mockQueryBuilderForCount(2)    // failed_count
            );

        $result = $this->stats->getStatistics();

        self::assertIsArray($result);
        self::assertArrayHasKey('total_income', $result);
        self::assertArrayHasKey('total_count', $result);
        self::assertArrayHasKey('this_month', $result);
        self::assertArrayHasKey('this_month_count', $result);
        self::assertArrayHasKey('pending_count', $result);
        self::assertArrayHasKey('failed_count', $result);
        self::assertArrayHasKey('recent_payments', $result);

        self::assertEquals(15, $result['total_count']);
        self::assertEquals(8, $result['this_month_count']);
        self::assertEquals(3, $result['pending_count']);
        self::assertEquals(2, $result['failed_count']);
        self::assertEquals($recentPayments, $result['recent_payments']);

        // Verify income structure
        self::assertArrayHasKey('USD', $result['total_income']);
        self::assertArrayHasKey('amount', $result['total_income']['USD']);
        self::assertArrayHasKey('currency', $result['total_income']['USD']);
        self::assertInstanceOf(BigInteger::class, $result['total_income']['USD']['amount']);
        self::assertInstanceOf(Currency::class, $result['total_income']['USD']['currency']);
        self::assertTrue($result['total_income']['USD']['amount']->isEqualTo(100000));
        self::assertEquals('USD', $result['total_income']['USD']['currency']->getCode());

        self::assertArrayHasKey('USD', $result['this_month']);
        self::assertArrayHasKey('amount', $result['this_month']['USD']);
        self::assertArrayHasKey('currency', $result['this_month']['USD']);
        self::assertInstanceOf(BigInteger::class, $result['this_month']['USD']['amount']);
        self::assertInstanceOf(Currency::class, $result['this_month']['USD']['currency']);
        self::assertTrue($result['this_month']['USD']['amount']->isEqualTo(25000));
        self::assertEquals('USD', $result['this_month']['USD']['currency']->getCode());
    }

    /**
     * @throws MathException
     * @throws DateMalformedStringException
     */
    public function testGetStatisticsWithMultipleCurrencies(): void
    {
        $totalIncome = [
            'USD' => BigInteger::of(100000),
            'EUR' => BigInteger::of(85000),
            'GBP' => BigInteger::of(72000),
        ];
        $thisMonthIncome = [
            'USD' => BigInteger::of(25000),
            'EUR' => BigInteger::of(18000),
        ];

        $this->repository->expects($this->once())
            ->method('getTotalIncome')
            ->willReturn($totalIncome);

        $this->repository->expects($this->once())
            ->method('getPaymentsThisMonth')
            ->willReturn($thisMonthIncome);

        $this->repository->expects($this->once())
            ->method('getRecentPayments')
            ->with(5)
            ->willReturn([]);

        // Mock query builder for count queries
        $this->repository->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturnOnConsecutiveCalls(
                $this->mockQueryBuilderForCount(20),
                $this->mockQueryBuilderForCount(10),
                $this->mockQueryBuilderForCount(5),
                $this->mockQueryBuilderForCount(1)
            );

        $result = $this->stats->getStatistics();

        // Verify all currencies in total_income
        self::assertCount(3, $result['total_income']);
        self::assertArrayHasKey('USD', $result['total_income']);
        self::assertArrayHasKey('EUR', $result['total_income']);
        self::assertArrayHasKey('GBP', $result['total_income']);

        self::assertTrue($result['total_income']['USD']['amount']->isEqualTo(100000));
        self::assertTrue($result['total_income']['EUR']['amount']->isEqualTo(85000));
        self::assertTrue($result['total_income']['GBP']['amount']->isEqualTo(72000));

        // Verify currencies in this_month
        self::assertCount(2, $result['this_month']);
        self::assertArrayHasKey('USD', $result['this_month']);
        self::assertArrayHasKey('EUR', $result['this_month']);

        self::assertTrue($result['this_month']['USD']['amount']->isEqualTo(25000));
        self::assertTrue($result['this_month']['EUR']['amount']->isEqualTo(18000));
    }

    /**
     * @throws MathException
     * @throws DateMalformedStringException
     */
    public function testGetStatisticsWithNoPayments(): void
    {
        $this->repository->expects($this->once())
            ->method('getTotalIncome')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getPaymentsThisMonth')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getRecentPayments')
            ->with(5)
            ->willReturn([]);

        // Mock query builder for count queries - all zeros
        $this->repository->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturnOnConsecutiveCalls(
                $this->mockQueryBuilderForCount(0),
                $this->mockQueryBuilderForCount(0),
                $this->mockQueryBuilderForCount(0),
                $this->mockQueryBuilderForCount(0)
            );

        $result = $this->stats->getStatistics();

        self::assertEquals([], $result['total_income']);
        self::assertEquals(0, $result['total_count']);
        self::assertEquals([], $result['this_month']);
        self::assertEquals(0, $result['this_month_count']);
        self::assertEquals(0, $result['pending_count']);
        self::assertEquals(0, $result['failed_count']);
        self::assertEquals([], $result['recent_payments']);
    }

    /**
     * @throws MathException
     * @throws DateMalformedStringException
     */
    public function testGetStatisticsWithOnlyPendingAndFailedPayments(): void
    {
        $this->repository->expects($this->once())
            ->method('getTotalIncome')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getPaymentsThisMonth')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getRecentPayments')
            ->with(5)
            ->willReturn([]);

        // No captured payments, but some pending and failed
        $this->repository->expects($this->exactly(4))
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturnOnConsecutiveCalls(
                $this->mockQueryBuilderForCount(0),  // total_count (captured only)
                $this->mockQueryBuilderForCount(0),  // this_month_count (captured only)
                $this->mockQueryBuilderForCount(10), // pending_count
                $this->mockQueryBuilderForCount(5)   // failed_count
            );

        $result = $this->stats->getStatistics();

        self::assertEquals(0, $result['total_count']);
        self::assertEquals(0, $result['this_month_count']);
        self::assertEquals(10, $result['pending_count']);
        self::assertEquals(5, $result['failed_count']);
    }

    private function mockQueryBuilderForCount(int $count): QueryBuilder
    {
        /** @var QueryBuilder&MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);
        /** @var AbstractQuery&MockObject $query */
        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('COUNT(p.id)')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();

        $queryBuilder->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);

        return $queryBuilder;
    }
}
