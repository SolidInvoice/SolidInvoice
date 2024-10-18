<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Tests\Serializer\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Serializer\Normalilzer\AddressNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AddressNormalizerTest extends TestCase
{
    private AddressNormalizer $normalizer;

    private ManagerRegistry | MockObject $registry;

    private DenormalizerInterface | MockObject $denormalizer;

    private NormalizerInterface | MockObject $innerNormalizer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->innerNormalizer = $this->createMock(NormalizerInterface::class);

        $this->normalizer = new AddressNormalizer($this->registry);
        $this->normalizer->setDenormalizer($this->denormalizer);
        $this->normalizer->setNormalizer($this->innerNormalizer);
    }

    public function testDenormalizesAddressWithClientId(): void
    {
        $data = ['street1' => '123 Main St'];
        $context = ['uri_variables' => ['clientId' => 1]];
        $client = new Client();
        $address = new Address();

        $clientRepository = $this->createMock(ObjectRepository::class);
        $clientRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Client::class)
            ->willReturn($clientRepository);

        $this->denormalizer->expects($this->once())
            ->method('denormalize')
            ->with($data, Address::class, null, $context + [AddressNormalizer::class => true])
            ->willReturn($address);

        $result = $this->normalizer->denormalize($data, Address::class, null, $context);

        self::assertSame($address, $result);
        self::assertSame($client, $result->getClient());
    }

    public function testDenormalizesAddressWithoutClientId(): void
    {
        $data = ['street' => '123 Main St'];
        $context = [];
        $address = new Address();

        $this->denormalizer->expects($this->once())
            ->method('denormalize')
            ->with($data, Address::class, null, $context + [AddressNormalizer::class => true])
            ->willReturn($address);

        $result = $this->normalizer->denormalize($data, Address::class, null, $context);

        $this->assertSame($address, $result);
        $this->assertNull($result->getClient());
    }

    public function testSupportsDenormalizationForAddress(): void
    {
        $data = ['street' => '123 Main St'];
        $context = [];

        $result = $this->normalizer->supportsDenormalization($data, Address::class, null, $context);

        $this->assertTrue($result);
    }

    public function testDoesNotSupportDenormalizationForNonAddress(): void
    {
        $data = ['street' => '123 Main St'];
        $context = [];

        $result = $this->normalizer->supportsDenormalization($data, Client::class, null, $context);

        $this->assertFalse($result);
    }

    public function testNormalizesAddress(): void
    {
        $address = new Address();
        $context = [];
        $normalizedData = ['street' => '123 Main St'];

        $this->innerNormalizer->expects($this->once())
            ->method('normalize')
            ->with($address, null, $context + [AddressNormalizer::class => true])
            ->willReturn($normalizedData);

        $result = $this->normalizer->normalize($address, null, $context);

        $this->assertSame($normalizedData, $result);
    }

    public function testSupportsNormalizationForAddress(): void
    {
        $address = new Address();
        $context = [];

        $result = $this->normalizer->supportsNormalization($address, null, $context);

        $this->assertTrue($result);
    }

    public function testDoesNotSupportNormalizationForNonAddress(): void
    {
        $client = new Client();
        $context = [];

        $result = $this->normalizer->supportsNormalization($client, null, $context);

        $this->assertFalse($result);
    }
}
