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

namespace SolidInvoice\CoreBundle\Tests\Serializer\Normalizer;

use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Serializer\Normalizer\CustomFieldsDenormalizer;
use SolidInvoice\CoreBundle\Serializer\Normalizer\CustomFieldsNormalizer;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldStagingStore;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Asserts that the API surface respects the `custom_fields` feature gate:
 * the normalizer disables itself entirely so responses do not contain a
 * `customFields` key, and the denormalizer rejects writes loudly rather
 * than silently dropping the payload.
 */
final class CustomFieldsNormalizerGateTest extends TestCase
{
    public function testNormalizerDoesNotSupportWhenFeatureDisabled(): void
    {
        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturnCallback(static fn (string $key): bool => $key !== 'custom_fields');

        $normalizer = new CustomFieldsNormalizer(
            $this->createStub(CustomFieldRepository::class),
            $this->createStub(CustomFieldValueRepository::class),
            new CustomFieldTypeResolver(),
            $gate,
            $this->createStub(CompanySelectorInterface::class),
        );

        self::assertFalse($normalizer->supportsNormalization(new Client()));
    }

    public function testNormalizerSupportsWhenFeatureEnabled(): void
    {
        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn(true);

        $normalizer = new CustomFieldsNormalizer(
            $this->createStub(CustomFieldRepository::class),
            $this->createStub(CustomFieldValueRepository::class),
            new CustomFieldTypeResolver(),
            $gate,
            $this->createStub(CompanySelectorInterface::class),
        );

        self::assertTrue($normalizer->supportsNormalization(new Client()));
    }

    public function testDenormalizerRejectsCustomFieldsWriteWhenFeatureDisabled(): void
    {
        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn(false);

        $inner = $this->createStub(DenormalizerInterface::class);
        $denormalizer = new CustomFieldsDenormalizer(
            $this->createStub(CustomFieldRepository::class),
            new CustomFieldTypeResolver(),
            $gate,
            new CustomFieldStagingStore(),
        );
        $denormalizer->setDenormalizer($inner);

        $this->expectException(UnexpectedValueException::class);
        $denormalizer->denormalize(
            ['name' => 'Acme', 'customFields' => ['note' => 'private']],
            Client::class,
        );
    }

    public function testDenormalizerSilentlyDropsEmptyCustomFieldsWhenFeatureDisabled(): void
    {
        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn(false);

        $inner = $this->createMock(DenormalizerInterface::class);
        $inner->expects(self::once())->method('denormalize')->willReturn(new Client());

        $denormalizer = new CustomFieldsDenormalizer(
            $this->createStub(CustomFieldRepository::class),
            new CustomFieldTypeResolver(),
            $gate,
            new CustomFieldStagingStore(),
        );
        $denormalizer->setDenormalizer($inner);

        $result = $denormalizer->denormalize(
            ['name' => 'Acme'],
            Client::class,
        );

        self::assertInstanceOf(Client::class, $result);
    }
}
