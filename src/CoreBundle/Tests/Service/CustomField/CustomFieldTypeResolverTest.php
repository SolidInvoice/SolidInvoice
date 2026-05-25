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

namespace SolidInvoice\CoreBundle\Tests\Service\CustomField;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;

final class CustomFieldTypeResolverTest extends TestCase
{
    private CustomFieldTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomFieldTypeResolver();
    }

    /**
     * @param list<array{value: string, label: string}>|null $options
     */
    #[DataProvider('roundTripData')]
    public function testRoundTrip(CustomFieldType $type, mixed $input, ?string $stored, mixed $deserialized, ?array $options = null): void
    {
        $field = new CustomField()->setType($type)->setOptions($options);
        self::assertSame($stored, $this->resolver->serialize($field, $input));
        self::assertEquals($deserialized, $this->resolver->deserialize($field, $stored));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function roundTripData(): iterable
    {
        yield 'text' => [CustomFieldType::TEXT, 'hello', 'hello', 'hello'];
        yield 'textarea' => [CustomFieldType::TEXTAREA, "line1\nline2", "line1\nline2", "line1\nline2"];
        yield 'number int' => [CustomFieldType::NUMBER, 42, '42', 42];
        yield 'number float' => [CustomFieldType::NUMBER, 3.14, '3.14', 3.14];
        yield 'date' => [CustomFieldType::DATE, CarbonImmutable::parse('2026-04-24'), '2026-04-24', CarbonImmutable::parse('2026-04-24')];
        yield 'email' => [CustomFieldType::EMAIL, 'a@b.com', 'a@b.com', 'a@b.com'];
        yield 'url' => [CustomFieldType::URL, 'https://x.com', 'https://x.com', 'https://x.com'];
        yield 'checkbox true' => [CustomFieldType::CHECKBOX, true, '1', true];
        yield 'checkbox false' => [CustomFieldType::CHECKBOX, false, '0', false];
        yield 'select' => [CustomFieldType::SELECT, 'gold', 'gold', 'gold', [['value' => 'gold', 'label' => 'Gold']]];
        yield 'multi-select' => [CustomFieldType::MULTI_SELECT, ['a', 'b'], '["a","b"]', ['a', 'b'], [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']]];
        yield 'multi-select empty' => [CustomFieldType::MULTI_SELECT, [], '[]', []];
    }

    public function testNullSerialize(): void
    {
        $field = new CustomField()->setType(CustomFieldType::TEXT);
        self::assertNull($this->resolver->serialize($field, null));
        self::assertNull($this->resolver->serialize($field, ''));
    }

    public function testNullDeserialize(): void
    {
        $field = new CustomField()->setType(CustomFieldType::TEXT);
        self::assertNull($this->resolver->deserialize($field, null));
    }
}
