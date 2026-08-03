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

namespace SolidInvoice\ClientBundle\Tests\Form\Type;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Money\Currency;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Form\Type\CreditType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreditTypeTest extends FormTestCase
{
    /**
     * @throws MathException
     */
    public function testSubmit(): void
    {
        $amount = $this->faker->numberBetween(0, 10000);

        $formData = [
            'amount' => $amount,
        ];

        $object = [
            'amount' => BigDecimal::of($amount * 100),
        ];

        $this->assertFormData(CreditType::class, $formData, $object, ['currency' => new Currency('USD')]);
    }

    /**
     * The amount is entered in major units and stored in minor units, so the factor follows the
     * number of decimals the currency has. This previously passed a random `faker->currencyCode()`
     * while asserting a fixed x100, which passed or failed depending on the currency drawn.
     *
     * @throws MathException
     */
    #[DataProvider('currencyProvider')]
    public function testSubmitScalesByCurrencySubunit(string $currencyCode, int $amount, string $expected): void
    {
        $this->assertFormData(
            CreditType::class,
            ['amount' => $amount],
            ['amount' => BigDecimal::of($expected)],
            ['currency' => new Currency($currencyCode)]
        );
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function currencyProvider(): iterable
    {
        yield 'two decimals' => ['USD', 2312, '231200'];
        yield 'zero decimals' => ['JPY', 2312, '2312'];
        yield 'three decimals' => ['BHD', 2312, '2312000'];
    }

    /**
     * @return list<FormTypeInterface>
     */
    #[Override]
    protected function getTypes(): array
    {
        return [
            new CreditType($this->createStub(TranslatorInterface::class)),
        ];
    }
}
