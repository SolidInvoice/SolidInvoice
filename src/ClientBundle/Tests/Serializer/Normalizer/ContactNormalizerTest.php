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

namespace SolidInvoice\ClientBundle\Tests\Serializer\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Serializer\Normalilzer\ContactNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ContactNormalizerTest extends TestCase
{
    private ContactNormalizer $normalizer;

    private ManagerRegistry&Stub $registry;

    private DenormalizerInterface&Stub $denormalizer;

    private NormalizerInterface&Stub $innerNormalizer;

    protected function setUp(): void
    {
        $this->registry = $this->createStub(ManagerRegistry::class);
        $this->denormalizer = $this->createStub(DenormalizerInterface::class);
        $this->innerNormalizer = $this->createStub(NormalizerInterface::class);

        $this->normalizer = new ContactNormalizer($this->registry);
        $this->normalizer->setDenormalizer($this->denormalizer);
        $this->normalizer->setNormalizer($this->innerNormalizer);
    }

    public function testDenormalizesContactWithClientId(): void
    {
        $data = ['street1' => '123 Main St'];
        $context = ['uri_variables' => ['clientId' => 1]];
        $client = new Client();
        $contact = new Contact();

        $clientRepository = $this->createMock(ObjectRepository::class);
        $clientRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())
            ->method('getRepository')
            ->with(Client::class)
            ->willReturn($clientRepository);

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->once())
            ->method('denormalize')
            ->with($data, Contact::class, null, $context + [ContactNormalizer::class => true])
            ->willReturn($contact);

        $normalizer = new ContactNormalizer($registry);
        $normalizer->setDenormalizer($denormalizer);
        $normalizer->setNormalizer($this->innerNormalizer);

        $result = $normalizer->denormalize($data, Contact::class, null, $context);

        self::assertSame($contact, $result);
        self::assertSame($client, $result->getClient());
    }

    public function testDenormalizesContactWithoutClientId(): void
    {
        $data = ['street' => '123 Main St'];
        $context = [];
        $contact = new Contact();

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->once())
            ->method('denormalize')
            ->with($data, Contact::class, null, $context + [ContactNormalizer::class => true])
            ->willReturn($contact);

        $this->normalizer->setDenormalizer($denormalizer);

        $result = $this->normalizer->denormalize($data, Contact::class, null, $context);

        $this->assertSame($contact, $result);
        $this->assertNull($result->getClient());
    }

    public function testSupportsDenormalizationForContact(): void
    {
        $data = ['street' => '123 Main St'];
        $context = [];

        $result = $this->normalizer->supportsDenormalization($data, Contact::class, null, $context);

        $this->assertTrue($result);
    }

    public function testDoesNotSupportDenormalizationForNonContact(): void
    {
        $data = ['street' => '123 Main St'];
        $context = [];

        $result = $this->normalizer->supportsDenormalization($data, Client::class, null, $context);

        $this->assertFalse($result);
    }

    public function testNormalizesContact(): void
    {
        $contact = new Contact();
        $context = [];
        $normalizedData = ['street' => '123 Main St'];

        $innerNormalizer = $this->createMock(NormalizerInterface::class);
        $innerNormalizer->expects($this->once())
            ->method('normalize')
            ->with($contact, null, $context + [ContactNormalizer::class => true])
            ->willReturn($normalizedData);

        $this->normalizer->setNormalizer($innerNormalizer);

        $result = $this->normalizer->normalize($contact, null, $context);

        $this->assertSame($normalizedData, $result);
    }

    public function testSupportsNormalizationForContact(): void
    {
        $contact = new Contact();
        $context = [];

        $result = $this->normalizer->supportsNormalization($contact, null, $context);

        $this->assertTrue($result);
    }

    public function testDoesNotSupportNormalizationForNonContact(): void
    {
        $client = new Client();
        $context = [];

        $result = $this->normalizer->supportsNormalization($client, null, $context);

        $this->assertFalse($result);
    }
}
