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

namespace SolidInvoice\EInvoiceBundle\Tests\Repository;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;
use SolidInvoice\EInvoiceBundle\Repository\EInvoiceDocumentRepository;
use SolidInvoice\EInvoiceBundle\Test\Factory\EInvoiceDocumentFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function array_map;

#[CoversClass(EInvoiceDocumentRepository::class)]
final class EInvoiceDocumentRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private EInvoiceDocumentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');

        $this->repository = new EInvoiceDocumentRepository($registry);
    }

    public function testFindForInvoiceOrdersNewestFirst(): void
    {
        $invoice = InvoiceFactory::createOne(['company' => $this->company]);

        $oldest = EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'invoice' => $invoice,
            'created' => new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC')),
        ]);
        $newest = EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'invoice' => $invoice,
            'created' => new DateTimeImmutable('2024-03-01 00:00:00', new DateTimeZone('UTC')),
        ]);
        $middle = EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'invoice' => $invoice,
            'created' => new DateTimeImmutable('2024-02-01 00:00:00', new DateTimeZone('UTC')),
        ]);

        $results = $this->repository->findForInvoice($invoice);

        self::assertSame(
            [$newest->getId()->toString(), $middle->getId()->toString(), $oldest->getId()->toString()],
            array_map(static fn ($document) => $document->getId()->toString(), $results),
        );
    }

    public function testFindDueForPollingBoundary(): void
    {
        $now = new DateTimeImmutable('2024-06-01 12:00:00', new DateTimeZone('UTC'));

        $due = EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'status' => EInvoiceStatus::Transmitted,
            'pollAfter' => $now,
        ]);
        EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'status' => EInvoiceStatus::Transmitted,
            'pollAfter' => $now->modify('+1 hour'),
        ]);
        EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'status' => EInvoiceStatus::Queued,
            'pollAfter' => $now->modify('-1 hour'),
        ]);

        $results = $this->repository->findDueForPolling($now);

        self::assertCount(1, $results);
        self::assertSame($due->getId()->toString(), $results[0]->getId()->toString());
    }
}
