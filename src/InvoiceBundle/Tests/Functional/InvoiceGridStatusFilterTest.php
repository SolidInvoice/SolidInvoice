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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\DataGrid\InvoiceGrid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InvoiceGridStatusFilterTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testStatusFilter(): void
    {
        $grid = self::getContainer()->get(InvoiceGrid::class);

        $statusColumn = null;
        foreach ($grid->columns() as $column) {
            if ($column->getField() === 'status') {
                $statusColumn = $column;

                break;
            }
        }

        self::assertNotNull($statusColumn, 'The invoice grid must have a status column');

        $filter = $statusColumn->getFilter();
        self::assertInstanceOf(ChoiceFilter::class, $filter);

        $choices = $filter->formOptions()['choices'];

        self::assertCount(7, $choices);
    }
}
