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

namespace SolidInvoice\PaymentBundle\DummyData;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Faker\Generator;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoaderInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use function assert;

final class PaymentDummyDataLoader implements DummyDataLoaderInterface
{
    private readonly Generator $faker;

    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
        $this->faker = Factory::create();
    }

    public static function getPriority(): int
    {
        return 60;
    }

    public function load(Company $company): void
    {
        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);

        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $em->getRepository(Invoice::class);

        /** @var Invoice[] $paidInvoices */
        $paidInvoices = $invoiceRepository->findBy(['status' => InvoiceStatus::Paid]);

        foreach ($paidInvoices as $invoice) {
            $client = $invoice->getClient();

            if (null === $client) {
                continue;
            }

            $currencyCode = $client->getCurrencyCode() ?? 'USD';

            $payment = new Payment();
            $payment->setInvoice($invoice)
                ->setClient($client)
                ->setStatus(PaymentStatus::Captured)
                ->setReference($this->faker->numerify('REF-######'))
                ->setCompany($company);
            $payment->setNumber($this->faker->numerify('PAY-######'));
            $payment->setTotalAmount($invoice->getTotal()->toBigInteger()->toInt());
            $payment->setCurrencyCode($currencyCode);
            $payment->setCreated($invoice->getPaidDate());

            $firstContact = $client->getContacts()->first();
            if (false !== $firstContact) {
                $payment->setClientEmail($firstContact->getEmail());
            }

            $paidDate = $invoice->getPaidDate();
            if (null !== $paidDate) {
                $payment->setCompleted(new DateTime($paidDate->format('Y-m-d H:i:s')));
            }

            $em->persist($payment);
        }

        $em->flush();
    }
}
