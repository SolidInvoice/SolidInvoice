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

namespace SolidInvoice\PaymentBundle\Tests\DataGrid;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\PaymentBundle\DataGrid\PaymentsGrid;
use SolidInvoice\PaymentBundle\Entity\Payment;

#[CoversClass(PaymentsGrid::class)]
final class PaymentsGridTest extends TestCase
{
    private PaymentsGrid $grid;

    protected function setUp(): void
    {
        $this->grid = new PaymentsGrid();
    }

    public function testEntityFQCNReturnsPaymentClass(): void
    {
        self::assertSame(Payment::class, $this->grid->entityFQCN());
    }

    public function testColumnsCount(): void
    {
        self::assertCount(8, $this->grid->columns());
    }

    /**
     * Relationship fields must not be searchable — applying LIKE to them in DQL
     * produces a Doctrine "Invalid PathExpression" semantical error.
     */
    public function testRelationshipColumnsAreNotSearchable(): void
    {
        $columns = $this->grid->columns();

        $byField = [];
        foreach ($columns as $column) {
            $byField[$column->getField()] = $column;
        }

        self::assertFalse($byField['invoice']->isSearchable(), 'invoice (ManyToOne) must not be searchable');
        self::assertFalse($byField['client']->isSearchable(), 'client (ManyToOne) must not be searchable');
        self::assertFalse($byField['method']->isSearchable(), 'method (ManyToOne) must not be searchable');
    }

    /**
     * The 'amount' column is a virtual getter — the underlying DB column is
     * 'totalAmount'.  Searching on 'amount' via LIKE generates invalid DQL.
     */
    public function testAmountColumnIsNotSearchable(): void
    {
        $columns = $this->grid->columns();

        $byField = [];
        foreach ($columns as $column) {
            $byField[$column->getField()] = $column;
        }

        self::assertFalse($byField['amount']->isSearchable(), 'amount (virtual field) must not be searchable');
    }

    public function testScalarColumnsRemainSearchable(): void
    {
        $columns = $this->grid->columns();

        $byField = [];
        foreach ($columns as $column) {
            $byField[$column->getField()] = $column;
        }

        self::assertTrue($byField['status']->isSearchable(), 'status should be searchable');
        self::assertTrue($byField['message']->isSearchable(), 'message should be searchable');
    }

    public function testColumnTypes(): void
    {
        $columns = $this->grid->columns();

        $byField = [];
        foreach ($columns as $column) {
            $byField[$column->getField()] = $column;
        }

        self::assertInstanceOf(StringColumn::class, $byField['invoice']);
        self::assertInstanceOf(StringColumn::class, $byField['client']);
        self::assertInstanceOf(StringColumn::class, $byField['method']);
        self::assertInstanceOf(StringColumn::class, $byField['status']);
        self::assertInstanceOf(DateTimeColumn::class, $byField['completed']);
        self::assertInstanceOf(StringColumn::class, $byField['message']);
        self::assertInstanceOf(MoneyColumn::class, $byField['amount']);
        self::assertInstanceOf(DateTimeColumn::class, $byField['created']);
    }
}
