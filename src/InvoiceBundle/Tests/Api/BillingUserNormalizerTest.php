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

namespace SolidInvoice\InvoiceBundle\Tests\Api;

use ApiPlatform\Api\IriConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Api\BillingUserNormalizer;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @coversDefaultClass \SolidInvoice\InvoiceBundle\Api\BillingUserNormalizer
 */
final class BillingUserNormalizerTest extends TestCase
{
    private BillingUserNormalizer $billingUserNormalizer;

    /**
     * @var IriConverterInterface&MockObject
     */
    private IriConverterInterface $iriConverter;

    /**
     * @var DenormalizerInterface&MockObject
     */
    private DenormalizerInterface $denormalizer;

    /**
     * @var NormalizerInterface&MockObject
     */
    private NormalizerInterface $normalizer;

    protected function setUp(): void
    {
        $this->iriConverter = $this->createMock(IriConverterInterface::class);
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->normalizer = $this->createMock(NormalizerInterface::class);

        $this->billingUserNormalizer = new BillingUserNormalizer($this->iriConverter);
        $this->billingUserNormalizer->setDenormalizer($this->denormalizer);
        $this->billingUserNormalizer->setNormalizer($this->normalizer);
    }

    public function testSupportsDenormalization(): void
    {
        $data = ['users' => [
            new Contact()
        ]];
        $supportedClasses = [Invoice::class, RecurringInvoice::class, Quote::class];

        foreach ($supportedClasses as $class) {
            self::assertTrue($this->billingUserNormalizer->supportsDenormalization($data, $class, 'json'));
            self::assertTrue($this->billingUserNormalizer->supportsDenormalization($data, $class, 'jsonld'));
        }

        self::assertFalse($this->billingUserNormalizer->supportsDenormalization($data, stdClass::class, 'json'));
        self::assertFalse($this->billingUserNormalizer->supportsDenormalization([], Invoice::class, 'xml'));
    }

    public function testSupportsNormalization(): void
    {
        $context = ['resource_class' => Invoice::class];
        $data = ['users' => []];
        $supportedClasses = [Invoice::class, RecurringInvoice::class, Quote::class];

        foreach ($supportedClasses as $class) {
            self::assertTrue($this->billingUserNormalizer->supportsNormalization($data, 'json', ['resource_class' => $class]));
            self::assertTrue($this->billingUserNormalizer->supportsNormalization($data, 'jsonld', ['resource_class' => $class]));
        }

        self::assertTrue($this->billingUserNormalizer->supportsNormalization($data, 'xml', $context));
        self::assertFalse($this->billingUserNormalizer->supportsNormalization([], 'json', $context));
    }

    public function testDenormalize(): void
    {
        $data = ['users' => []];
        $class = Invoice::class;
        $invoice = new Invoice();

        $this->denormalizer
            ->expects(self::once())
            ->method('denormalize')
            ->with($data, $class, 'json', [BillingUserNormalizer::class => true])
            ->willReturn($invoice);

        self::assertSame($invoice, $this->billingUserNormalizer->denormalize($data, $class, 'json'));
    }

    public function testNormalize(): void
    {
        $object = ['users' => [$user = new stdClass()]];
        $format = 'json';
        $context = ['resource_class' => Invoice::class];

        $iri = '/some/iri';
        $this->iriConverter
            ->expects(self::once())
            ->method('getIriFromResource')
            ->with($user)
            ->willReturn($iri);

        $this->normalizer
            ->expects(self::once())
            ->method('normalize')
            ->with(['users' => [$iri]], $format, ['resource_class' => Invoice::class, BillingUserNormalizer::class => true])
            ->willReturn($normalized = ['users' => [$iri]]);

        $result = $this->billingUserNormalizer->normalize($object, $format, $context);

        self::assertSame($normalized, $result);
    }
}
