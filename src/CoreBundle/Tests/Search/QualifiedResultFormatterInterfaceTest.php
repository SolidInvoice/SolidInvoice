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

namespace SolidInvoice\CoreBundle\Tests\Search;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;

final class QualifiedResultFormatterInterfaceTest extends TestCase
{
    public function testExtendsResultFormatterInterface(): void
    {
        self::assertTrue(
            is_a(QualifiedResultFormatterInterface::class, ResultFormatterInterface::class, true)
        );
    }

    public function testGetSupportedQualifiersReturnsMap(): void
    {
        $formatter = new class() implements QualifiedResultFormatterInterface {
            public function getIndexName(): string
            {
                return 'test';
            }

            public function format(array $hit): SearchResult
            {
                return new SearchResult('t', 'i', 't', 's', 'u');
            }

            public function getSupportedQualifiers(): array
            {
                return ['status' => 'status', 'amount' => 'total'];
            }
        };

        self::assertSame(['status' => 'status', 'amount' => 'total'], $formatter->getSupportedQualifiers());
    }
}
