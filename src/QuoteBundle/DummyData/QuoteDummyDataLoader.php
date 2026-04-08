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

namespace SolidInvoice\QuoteBundle\DummyData;

use Brick\Math\BigDecimal;
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
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use function array_rand;
use function assert;
use function random_int;

final class QuoteDummyDataLoader implements DummyDataLoaderInterface
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
        return 80;
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
            QuoteStatus::Draft,
            QuoteStatus::Pending,
            QuoteStatus::Accepted,
            QuoteStatus::Declined,
            QuoteStatus::Cancelled,
        ];

        foreach ($clients as $client) {
            for ($i = 0; $i < 5; ++$i) {
                $quote = new Quote();
                $quote->setCompany($company);
                $quote->setClient($client);

                $status = $statuses[array_rand($statuses)];
                $quote->setStatus($status);

                if ($status !== QuoteStatus::Draft) {
                    $daysOut = random_int(30, 120);
                    $quote->setDue(new DateTimeImmutable('+' . $daysOut . ' days'));
                }

                if ($this->faker->boolean(60)) {
                    $quote->setNotes($this->faker->sentence(10));
                }

                if ($this->faker->boolean(40)) {
                    $quote->setTerms($this->faker->sentence(15));
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

                        if ($tax->getType() === Tax::TYPE_EXCLUSIVE) {
                            $taxAmount = $lineTotal->multipliedBy((string) $tax->getRate())->dividedBy(100, 0, RoundingMode::HalfUp);
                            $taxTotal = $taxTotal->plus($taxAmount);
                        }
                    }

                    $quote->addLine($line);
                }

                $total = $baseTotal->plus($taxTotal);

                $quote->setBaseTotal($baseTotal)
                    ->setTax($taxTotal)
                    ->setTotal($total);

                $firstContact = $client->getContacts()->first();
                if (false !== $firstContact) {
                    $quote->addUser($firstContact);
                }

                $quote->setQuoteId($this->billingIdGenerator->generate($quote, ['field' => 'quoteId']));

                $em->persist($quote);
            }
            $em->flush();
        }
    }
}
