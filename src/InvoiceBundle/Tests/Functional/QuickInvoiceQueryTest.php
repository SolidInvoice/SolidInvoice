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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class QuickInvoiceQueryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testCanFindInvoiceNeedingReminder(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => (new DateTimeImmutable())->modify('+3 days')->setTime(0, 0)->modify('+6 hours'),
            'users' => [$contact],
        ]);

        $repository = self::getContainer()->get(InvoiceRepository::class);
        $results = iterator_to_array($repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(1, $results, 'Should find the invoice');
    }
}
