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

namespace SolidInvoice\EInvoiceBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\EInvoiceBundle\Mapper\BuyerMapper;
use SolidInvoice\EInvoiceBundle\Mapper\InvoiceLineMapper;
use SolidInvoice\EInvoiceBundle\Mapper\InvoiceMapper;
use SolidInvoice\EInvoiceBundle\Mapper\MinorUnitConverter;
use SolidInvoice\EInvoiceBundle\Mapper\SellerMapper;
use SolidInvoice\EInvoiceBundle\Mapper\VatBreakdownMapper;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class InvoiceMapperTest extends WebTestCase
{
    use DoctrineTestTrait;

    /**
     * The hazard design §5.3/§13.1 names rather than assumes: `TaxCalculator::calculate()` calls
     * `Line::updateTotal()` on every line, writing to a managed entity. For a freshly loaded
     * invoice this should be a no-op in value terms, so Doctrine's change tracker should compute
     * no changeset and no `UPDATE` should reach `invoice_lines`.
     *
     * This used to dirty every line: `Line::updateTotal()` unconditionally assigned a fresh
     * `Brick\Math\BigDecimal`, and `UnitOfWork::computeChangeSets()` compares old and new values
     * with `===`, so a freshly constructed value object never matched the hydrated one even when
     * numerically equal (see {@see \SolidInvoice\InvoiceBundle\Entity\Line::updateTotal()} for the
     * fix — SOL-334). Mapping an invoice runs `TaxCalculator::calculate()`, so this is the
     * e-invoicing side of that defect.
     */
    public function testMappingAFreshlyLoadedInvoiceIssuesNoLineUpdate(): void
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company, 'currencyCode' => 'EUR']);

        $invoice = InvoiceFactory::createOne([
            'company' => $company,
            'client' => $client,
            'total' => 1000,
            'baseTotal' => 1000,
            'tax' => 0,
            'balance' => 1000,
            'discount' => new Discount(),
        ]);

        $line = new Line();
        $line->setName('Consulting');
        $line->setPrice(1000);
        $line->setQty(1);
        $line->updateTotal();
        $line->addTax($this->standardVat());

        $invoice->addLine($line);

        $this->em->flush();
        $this->em->clear();

        self::getContainer()->get(CompanySelector::class)->switchCompany($company->getId());

        /** @var Invoice $freshInvoice */
        $freshInvoice = $this->em->getRepository(Invoice::class)->find($invoice->getId());

        $this->mapper()->map($freshInvoice);

        $this->em->getUnitOfWork()->computeChangeSets();

        $scheduledLines = array_filter(
            $this->em->getUnitOfWork()->getScheduledEntityUpdates(),
            static fn (object $scheduled): bool => $scheduled instanceof Line,
        );

        self::assertCount(0, $scheduledLines, 'Mapping the invoice must not schedule a line update');
    }

    /**
     * Design §11.2 flagged `TaxIdentifierRepository::findCompanyIdentifiers()` as unverified
     * against `CompanyFilter`. The shipped mapper does not call it —
     * {@see \SolidInvoice\EInvoiceBundle\Mapper\BuyerMapper} reads BT-48 off
     * `Client::getTaxIdentifiers()`, a direct relation on the already company-scoped
     * `Client` entity — so this proves the path actually taken: mapping company A's invoice
     * never surfaces company B's tax identifier, even when both exist in the same database.
     */
    public function testMappingNeverReadsAnotherCompanysTaxIdentifier(): void
    {
        $companyA = CompanyFactory::createOne(['name' => 'Company A']);
        $companyB = CompanyFactory::createOne(['name' => 'Company B']);

        $clientA = ClientFactory::createOne(['company' => $companyA, 'currencyCode' => 'EUR']);
        $clientB = ClientFactory::createOne(['company' => $companyB, 'currencyCode' => 'EUR']);

        $identifierA = new TaxIdentifier();
        $identifierA->setLabel('VAT');
        $identifierA->setValue('COMPANY-A-VAT');
        $identifierA->setCompany($companyA);
        $identifierA->setClient($clientA);

        $clientA->addTaxIdentifier($identifierA);

        $identifierB = new TaxIdentifier();
        $identifierB->setLabel('VAT');
        $identifierB->setValue('COMPANY-B-VAT');
        $identifierB->setCompany($companyB);
        $identifierB->setClient($clientB);

        $clientB->addTaxIdentifier($identifierB);

        $invoiceA = InvoiceFactory::createOne([
            'company' => $companyA,
            'client' => $clientA,
            'total' => 1000,
            'baseTotal' => 1000,
            'tax' => 0,
            'balance' => 1000,
            'discount' => new Discount(),
        ]);

        $line = new Line();
        $line->setName('Consulting');
        $line->setPrice(1000);
        $line->setQty(1);
        $line->updateTotal();
        $line->addTax($this->standardVat());

        $invoiceA->addLine($line);

        $this->em->flush();
        $this->em->clear();

        self::getContainer()->get(CompanySelector::class)->switchCompany($companyA->getId());

        /** @var Invoice $freshInvoiceA */
        $freshInvoiceA = $this->em->getRepository(Invoice::class)->find($invoiceA->getId());

        $eInvoice = $this->mapper()->map($freshInvoiceA);

        self::assertSame('COMPANY-A-VAT', $eInvoice->buyer->vatIdentifier);
    }

    private function mapper(): InvoiceMapper
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $converter = new MinorUnitConverter(new CurrencyScale());

        return new InvoiceMapper(
            new SellerMapper($systemConfig),
            new BuyerMapper(),
            new InvoiceLineMapper($converter),
            new VatBreakdownMapper($converter),
            $converter,
            self::getContainer()->get(TaxCalculatorInterface::class),
            self::getContainer()->get(Calculator::class),
            $systemConfig,
        );
    }

    private function standardVat(): LineTax
    {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('20.0000');
        $lineTax->setTypeSnapshot(TaxType::Exclusive);
        $lineTax->setCategorySnapshot(TaxCategory::Standard);
        $lineTax->setCompound(false);
        $lineTax->setSequence(0);

        return $lineTax;
    }
}
