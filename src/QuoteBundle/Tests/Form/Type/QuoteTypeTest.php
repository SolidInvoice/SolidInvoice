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

namespace SolidInvoice\QuoteBundle\Tests\Form\Type;

use Mockery as M;
use Money\Currency;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Type\ItemType;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use function bin2hex;
use function random_bytes;

class QuoteTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'client' => null,
            'discount' => 12,
            'quoteId' => '10',
            'lines' => [],
            'terms' => '',
            'notes' => '',
            'total' => 0,
            'baseTotal' => 0,
            'tax' => 123,
        ];

        $object = new Quote();

        $this->assertFormData($this->factory->create(QuoteType::class, $object), $formData, $object);
    }

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        $systemConfig
            ->shouldReceive('get')
            ->zeroOrMoreTimes()
            ->andReturn('random_number');

        $type = new QuoteType($systemConfig, $this->registry, new BillingIdGenerator(new ServiceLocator(['random_number' => static fn () => new class() {
            public function generate(): string
            {
                return '10';
            }
        }]), $systemConfig));
        $itemType = new ItemType($this->registry);

        return [
            new PreloadedExtension([$type, $itemType, new DiscountType($systemConfig)], []),
        ];
    }

    protected function getTypeExtensions(): array
    {
        return [
            new AutocompleteChoiceTypeExtension(
                new ChecksumCalculator($_SERVER['secret'] ?? bin2hex(random_bytes(8))),
            ),
        ];
    }
}
