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

namespace SolidInvoice\EInvoiceBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;

#[CoversClass(VatCategoryCode::class)]
final class VatCategoryCodeTest extends TestCase
{
    public function testCasesCoverUntdid5305Subset(): void
    {
        $values = array_map(static fn (VatCategoryCode $case): string => $case->value, VatCategoryCode::cases());

        self::assertSame(['S', 'Z', 'E', 'AE', 'K', 'G', 'O', 'L', 'M'], $values);
    }

    /**
     * @return iterable<string, array{VatCategoryCode, string}>
     */
    public static function labelProvider(): iterable
    {
        yield 'standard rate' => [VatCategoryCode::StandardRate, 'Standard rate'];
        yield 'zero rated' => [VatCategoryCode::ZeroRated, 'Zero rated goods'];
        yield 'exempt' => [VatCategoryCode::Exempt, 'Exempt from tax'];
        yield 'reverse charge' => [VatCategoryCode::ReverseCharge, 'VAT reverse charge'];
        yield 'intra-community supply' => [VatCategoryCode::IntraCommunitySupply, 'VAT exempt for EEA intra-community supply'];
        yield 'free export item' => [VatCategoryCode::FreeExportItem, 'Free export item, VAT not charged'];
        yield 'outside scope' => [VatCategoryCode::OutsideScope, 'Services outside scope of tax'];
        yield 'canary islands' => [VatCategoryCode::CanaryIslandsTax, 'Canary Islands general indirect tax'];
        yield 'ceuta melilla' => [VatCategoryCode::CeutaMelillaTax, 'Tax for production, services and importation in Ceuta and Melilla'];
    }

    #[DataProvider('labelProvider')]
    public function testGetLabel(VatCategoryCode $case, string $expected): void
    {
        self::assertSame($expected, $case->getLabel());
    }
}
