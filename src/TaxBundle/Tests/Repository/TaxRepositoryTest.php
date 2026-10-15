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

namespace SolidInvoice\TaxBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Repository\LineRepository as InvoiceLineRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Repository\LineRepository as QuoteLineRepository;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(TaxRepository::class)]
#[CoversClass(InvoiceLineRepository::class)]
#[CoversClass(QuoteLineRepository::class)]
final class TaxRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private TaxRepository $repository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Tax::class);
    }

    /**
     * Deleting a rate that is in use has to clear it off every invoice and quote line that
     * carries it, which is where the repository queries for the affected documents.
     */
    public function testDeleteTaxRatesRemovesARateThatIsStillInUse(): void
    {
        $tax = TaxFactory::createOne(['company' => $this->company]);

        $invoice = InvoiceFactory::createOne(['company' => $this->company, 'tax' => 100]);
        $invoiceLine = new InvoiceLine()
            ->setDescription('Invoice line')
            ->setPrice(1000)
            ->setQty(1)
            ->setTotal(1000)
            ->setInvoice($invoice);
        $invoice->addLine($invoiceLine);

        $quote = QuoteFactory::createOne(['company' => $this->company, 'tax' => 100]);
        $quoteLine = new QuoteLine()
            ->setDescription('Quote line')
            ->setPrice(1000)
            ->setQty(1)
            ->setTotal(1000)
            ->setQuote($quote);
        $quote->addLine($quoteLine);

        $this->em->persist($this->lineTax($tax)->setInvoiceLine($invoiceLine));
        $this->em->persist($this->lineTax($tax)->setQuoteLine($quoteLine));
        $this->em->persist($invoiceLine);
        $this->em->persist($quoteLine);
        $this->em->flush();

        self::assertCount(1, $this->repository->findAll());
        self::assertCount(2, $this->em->getRepository(LineTax::class)->findAll());

        $this->repository->deleteTaxRates([$tax->getId()]);

        // The rate is gone and both documents survived the recalculation it triggers. Before
        // this worked, the query behind that recalculation could not even be parsed.
        self::assertCount(0, $this->repository->findAll());
        self::assertCount(2, $this->em->getRepository(LineTax::class)->findAll());
    }

    private function lineTax(Tax $tax): LineTax
    {
        return new LineTax()
            ->setTax($tax)
            ->setNameSnapshot($tax->getName())
            ->setRateSnapshot($tax->getRate())
            ->setCategorySnapshot($tax->getCategory())
            ->setTypeSnapshot(TaxType::from($tax->getType()))
            ->setCompound(false)
            ->setSequence(0)
            ->setAmount(0)
            ->setCompany($this->company);
    }
}
