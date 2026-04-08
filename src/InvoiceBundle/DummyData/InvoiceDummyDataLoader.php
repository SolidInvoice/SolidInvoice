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

namespace SolidInvoice\InvoiceBundle\DummyData;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Faker\Generator;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoaderInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use function array_rand;
use function assert;
use function random_int;

final class InvoiceDummyDataLoader implements DummyDataLoaderInterface
{
    private readonly Generator $faker;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly BillingIdGenerator $billingIdGenerator,
    ) {
        $this->faker = Factory::create();
    }

    public static function getPriority(): int
    {
        return 70;
    }

    public function load(Company $company): void
    {
        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);

        /** @var ClientRepository $clientRepository */
        $clientRepository = $em->getRepository(Client::class);

        /** @var TaxRepository $taxRepository */
        $taxRepository = $em->getRepository(Tax::class);

        /** @var Client[] $clients */
        $clients = $clientRepository->findAll();

        /** @var Tax[] $taxes */
        $taxes = $taxRepository->findAll();

        $statuses = [
            InvoiceStatus::Draft,
            InvoiceStatus::Pending,
            InvoiceStatus::Paid,
            InvoiceStatus::Overdue,
        ];

        foreach ($clients as $client) {
            for ($i = 0; $i < 5; ++$i) {
                $invoice = new Invoice();
                $invoice->setCompany($company);
                $invoice->setClient($client);

                $status = $statuses[array_rand($statuses)];
                $invoice->setStatus($status);

                $daysAgo = random_int(1, 365);
                $invoiceDate = new DateTimeImmutable('-' . $daysAgo . ' days');
                $invoice->setInvoiceDate($invoiceDate);

                if ($status === InvoiceStatus::Pending || $status === InvoiceStatus::Overdue) {
                    $invoice->setDue(new DateTimeImmutable($invoiceDate->format('Y-m-d') . ' +30 days'));
                }

                if ($status === InvoiceStatus::Paid) {
                    $paidDaysAgo = random_int(1, $daysAgo);
                    $invoice->setPaidDate(new DateTimeImmutable('-' . $paidDaysAgo . ' days'));
                }

                if ($this->faker->boolean(60)) {
                    $invoice->setNotes($this->faker->sentence(10));
                }

                if ($this->faker->boolean(40)) {
                    $invoice->setTerms($this->faker->sentence(15));
                }

                $lineCount = random_int(2, 4);
                $baseTotal = BigDecimal::zero();
                $taxTotal = BigDecimal::zero();

                for ($j = 0; $j < $lineCount; ++$j) {
                    $price = random_int(500, 50000);
                    $qty = random_int(1, 10);

                    $line = new Line();
                    $line->setDescription($this->faker->sentence(5))
                        ->setPrice($price)
                        ->setQty($qty)
                        ->setCompany($company);

                    $lineTotal = BigDecimal::of($price)->multipliedBy($qty);
                    $baseTotal = $baseTotal->plus($lineTotal);

                    if ([] !== $taxes && $this->faker->boolean(50)) {
                        $tax = $taxes[array_rand($taxes)];
                        $line->setTax($tax);

                        if ($tax->getType() === Tax::TYPE_EXCLUSIVE && $tax->getRate() > 0.0) {
                            $taxAmount = $lineTotal->multipliedBy((string) $tax->getRate())->dividedBy(100, 0, RoundingMode::HalfUp);
                            $taxTotal = $taxTotal->plus($taxAmount);
                        }
                    }

                    $invoice->addLine($line);
                }

                $total = $baseTotal->plus($taxTotal);

                $invoice->setBaseTotal($baseTotal)
                    ->setTax($taxTotal)
                    ->setTotal($total);

                if ($status === InvoiceStatus::Paid) {
                    $invoice->setBalance(BigInteger::zero());
                } else {
                    $invoice->setBalance($total);
                }

                $firstContact = $client->getContacts()->first();
                if (false !== $firstContact) {
                    $invoice->addUser($firstContact);
                }

                $invoice->setInvoiceId($this->billingIdGenerator->generate($invoice, ['field' => 'invoiceId']));

                $em->persist($invoice);
            }

            $em->flush();
        }
    }
}
