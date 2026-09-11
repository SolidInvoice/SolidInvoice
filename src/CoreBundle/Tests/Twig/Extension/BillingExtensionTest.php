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

namespace SolidInvoice\CoreBundle\Tests\Twig\Extension;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Enum\UnitCode;
use SolidInvoice\CoreBundle\Form\FieldRenderer;
use SolidInvoice\CoreBundle\Twig\Extension\BillingExtension;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\MoneyBundle\Calculator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use function dirname;
use function sprintf;

#[CoversClass(BillingExtension::class)]
#[CoversClass(UnitCode::class)]
final class BillingExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{UnitCode, string, string}>
     */
    public static function quantityProvider(): iterable
    {
        yield 'bare number for a plain unit' => [UnitCode::UNIT, '2', '2'];
        yield 'unit appended' => [UnitCode::HOUR, '12', '12 hours'];
        yield 'symbol appended' => [UnitCode::SQUARE_METRE, '40.5', '40.5 m²'];
    }

    #[DataProvider('quantityProvider')]
    public function testQuantity(UnitCode $unitCode, string $qty, string $expected): void
    {
        $line = new Line()
            ->setQty(BigDecimal::of($qty))
            ->setUnitCode($unitCode);

        self::assertSame($expected, $this->createExtension()->quantity($line));
    }

    public function testEveryUnitCodeHasALabel(): void
    {
        // The label is what an invoice prints, so a case added without one would silently
        // render its own translation key next to the quantity.
        $translator = $this->createTranslator();

        foreach (UnitCode::cases() as $unitCode) {
            $key = 'line.unit.' . $unitCode->value;

            self::assertNotSame($key, $unitCode->trans($translator), sprintf('%s has no label', $unitCode->name));
        }
    }

    private function createExtension(): BillingExtension
    {
        return new BillingExtension(
            $this->createStub(FieldRenderer::class),
            new Calculator(),
            $this->createTranslator(),
        );
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('en');
        $translator->addLoader('yaml', new YamlFileLoader());
        // The real catalogue, so a case added without a label fails testEveryUnitCodeHasALabel.
        $translator->addResource('yaml', dirname(__DIR__, 5) . '/translations/messages.en.yml', 'en');

        return $translator;
    }
}
