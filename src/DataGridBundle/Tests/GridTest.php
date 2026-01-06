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

namespace SolidInvoice\DataGridBundle\Tests;

use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\Grid;

final class GridTest extends TestCase
{
    public function testHasRowDetailsReturnsFalseByDefault(): void
    {
        $grid = $this->createTestGrid();

        self::assertFalse($grid->hasRowDetails());
    }

    public function testGetRowDetailTemplateReturnsNullByDefault(): void
    {
        $grid = $this->createTestGrid();

        self::assertNull($grid->getRowDetailTemplate());
    }

    public function testHasRowDetailsCanBeOverridden(): void
    {
        $grid = new class() extends Grid {
            public function entityFQCN(): string
            {
                return \stdClass::class;
            }

            public function hasRowDetails(): bool
            {
                return true;
            }
        };

        self::assertTrue($grid->hasRowDetails());
    }

    public function testGetRowDetailTemplateCanBeOverridden(): void
    {
        $template = '@Test/detail.html.twig';

        $grid = new class($template) extends Grid {
            public function __construct(
                private readonly string $template
            ) {
            }

            public function entityFQCN(): string
            {
                return \stdClass::class;
            }

            public function hasRowDetails(): bool
            {
                return true;
            }

            public function getRowDetailTemplate(): ?string
            {
                return $this->template;
            }
        };

        self::assertSame($template, $grid->getRowDetailTemplate());
    }

    private function createTestGrid(): Grid
    {
        return new class() extends Grid {
            public function entityFQCN(): string
            {
                return \stdClass::class;
            }
        };
    }
}
