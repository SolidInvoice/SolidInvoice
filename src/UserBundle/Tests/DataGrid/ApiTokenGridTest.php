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

namespace SolidInvoice\UserBundle\Tests\DataGrid;

use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\DataGrid\ApiTokenGrid;
use SolidInvoice\UserBundle\Entity\ApiToken;
use Symfony\Bundle\SecurityBundle\Security;

final class ApiTokenGridTest extends TestCase
{
    private function createGrid(): ApiTokenGrid
    {
        $security = $this->createMock(Security::class);
        return new ApiTokenGrid($security);
    }

    public function testEntityFQCNReturnsApiTokenClass(): void
    {
        $grid = $this->createGrid();

        self::assertSame(ApiToken::class, $grid->entityFQCN());
    }

    public function testHasRowDetailsReturnsTrue(): void
    {
        $grid = $this->createGrid();

        self::assertTrue($grid->hasRowDetails());
    }

    public function testGetRowDetailTemplateReturnsCorrectPath(): void
    {
        $grid = $this->createGrid();

        self::assertSame(
            '@SolidInvoiceUser/Components/ApiTokenHistory.html.twig',
            $grid->getRowDetailTemplate()
        );
    }
}
