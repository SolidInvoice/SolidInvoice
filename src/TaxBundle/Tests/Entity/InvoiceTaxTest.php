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

namespace SolidInvoice\TaxBundle\Tests\Entity;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Validator\Constraints\ExactlyOneDocument;
use SolidInvoice\TaxBundle\Validator\Constraints\ExactlyOneDocumentValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(InvoiceTax::class)]
#[CoversClass(ExactlyOneDocument::class)]
#[CoversClass(ExactlyOneDocumentValidator::class)]
final class InvoiceTaxTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private ValidatorInterface $validator;

    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = self::getContainer()->get('validator');
        $this->registry = self::getContainer()->get('doctrine');
    }

    public function testValidatorRejectsWithNeitherDocumentSet(): void
    {
        // Validator only fires for persisted entities — new in-flight rows are
        // legitimately unwired during form binding, so simulate a persisted id.
        $invoiceTax = $this->buildInvoiceTax();
        $this->assignId($invoiceTax);

        $violations = $this->validator->validate($invoiceTax);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame((new ExactlyOneDocument())->message, $violations->get(0)->getMessage());
    }

    public function testValidatorSkipsUnpersistedRowsWithNoDocument(): void
    {
        $invoiceTax = $this->buildInvoiceTax();

        $violations = $this->validator->validate($invoiceTax);

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertNotContains((new ExactlyOneDocument())->message, $messages);
    }

    private function assignId(InvoiceTax $invoiceTax): void
    {
        $ref = new ReflectionProperty(InvoiceTax::class, 'id');
        $ref->setValue($invoiceTax, new Ulid());
    }

    public function testValidatorRejectsWithBothDocumentsSet(): void
    {
        $invoiceTax = $this->buildInvoiceTax();
        $invoiceTax->setInvoice(new Invoice());
        $invoiceTax->setQuote(new Quote());

        $violations = $this->validator->validate($invoiceTax);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame((new ExactlyOneDocument())->message, $violations->get(0)->getMessage());
    }

    public function testValidatorAcceptsWithOnlyInvoiceSet(): void
    {
        $invoiceTax = $this->buildInvoiceTax();
        $invoiceTax->setInvoice(new Invoice());

        self::assertCount(0, $this->validator->validate($invoiceTax));
    }

    public function testValidatorAcceptsWithOnlyQuoteSet(): void
    {
        $invoiceTax = $this->buildInvoiceTax();
        $invoiceTax->setQuote(new Quote());

        self::assertCount(0, $this->validator->validate($invoiceTax));
    }

    public function testCompanyIsInheritedFromParentInvoiceOnPersist(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company])->_real();
        $invoice = InvoiceFactory::createOne(['company' => $this->company, 'client' => $client])->_real();

        $invoiceTax = $this->buildInvoiceTax();
        $invoice->addInvoiceTax($invoiceTax);

        $em = $this->registry->getManagerForClass(InvoiceTax::class);
        self::assertInstanceOf(ObjectManager::class, $em);
        $em->persist($invoiceTax);
        $em->flush();

        self::assertSame(
            $this->company->getId()->toRfc4122(),
            $invoiceTax->getCompany()->getId()->toRfc4122(),
        );
    }

    public function testCompanyIsInheritedFromParentQuoteOnPersist(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company])->_real();
        $quote = QuoteFactory::createOne(['company' => $this->company, 'client' => $client])->_real();

        $invoiceTax = $this->buildInvoiceTax();
        $quote->addInvoiceTax($invoiceTax);

        $em = $this->registry->getManagerForClass(InvoiceTax::class);
        self::assertInstanceOf(ObjectManager::class, $em);
        $em->persist($invoiceTax);
        $em->flush();

        self::assertSame(
            $this->company->getId()->toRfc4122(),
            $invoiceTax->getCompany()->getId()->toRfc4122(),
        );
    }

    private function buildInvoiceTax(): InvoiceTax
    {
        $invoiceTax = new InvoiceTax();
        $invoiceTax->setNameSnapshot('Withholding');
        $invoiceTax->setRateSnapshot('10.0000');
        $invoiceTax->setCategorySnapshot(TaxCategory::Standard);
        $invoiceTax->setDirection(TaxDirection::Deductive);

        return $invoiceTax;
    }
}
