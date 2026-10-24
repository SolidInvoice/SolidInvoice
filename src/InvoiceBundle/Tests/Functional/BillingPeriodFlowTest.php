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

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceClientMode;
use SolidInvoice\InvoiceBundle\Manager\InvoiceFormManager;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use function Zenstruck\Foundry\Persistence\save;

/**
 * Guards {@see InvoiceFormManager::createDTOFromInvoice()} against silently dropping the
 * billing period it just saved: miss that mapping and the period survives the create but
 * vanishes the moment the invoice is reopened for editing.
 *
 * Also proves the period the form captures is the exact {@see \SolidInvoice\CoreBundle\Entity\BillingPeriod}
 * that {@see \SolidInvoice\CoreBundle\Validator\Constraints\ValidBillingPeriod} (from SOL-485) validates —
 * the form adds no ordering check of its own.
 */
#[CoversClass(InvoiceFormManager::class)]
final class BillingPeriodFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testPeriodSurvivesCreateAndReloadOnTheEditForm(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);

        $formManager = self::getContainer()->get(InvoiceFormManager::class);

        $dto = new InvoiceFormDTO();
        $dto->clientMode = InvoiceClientMode::Existing;
        $dto->client = $client;
        $dto->invoiceId = 'INV-PERIOD-1';
        $dto->discount = new Discount();
        $dto->invoiceDate = CarbonImmutable::parse('2026-02-01');
        $dto->periodStart = CarbonImmutable::parse('2026-02-01');
        $dto->periodEnd = CarbonImmutable::parse('2026-02-28');

        $invoice = $formManager->createInvoiceFromDTO($dto);

        /** @var WorkflowInterface $workflow */
        $workflow = self::getContainer()->get('test.state_machine.invoice');
        $workflow->apply($invoice, Graph::TRANSITION_NEW);

        save($invoice);

        $invoiceId = $invoice->getId();

        /** @var InvoiceRepository $repository */
        $repository = self::getContainer()->get(InvoiceRepository::class);
        self::getContainer()->get('doctrine')->getManager()->clear();

        $reloaded = $repository->find($invoiceId);
        self::assertInstanceOf(Invoice::class, $reloaded);

        $editDto = $formManager->createDTOFromInvoice($reloaded);

        self::assertNotNull($editDto->periodStart);
        self::assertNotNull($editDto->periodEnd);
        self::assertSame('2026-02-01', $editDto->periodStart->format('Y-m-d'));
        self::assertSame('2026-02-28', $editDto->periodEnd->format('Y-m-d'));
    }

    public function testInvertedPeriodFailsTheEntityConstraint(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);

        $formManager = self::getContainer()->get(InvoiceFormManager::class);

        $dto = new InvoiceFormDTO();
        $dto->clientMode = InvoiceClientMode::Existing;
        $dto->client = $client;
        $dto->invoiceId = 'INV-PERIOD-2';
        $dto->discount = new Discount();
        $dto->periodStart = CarbonImmutable::parse('2026-02-28');
        $dto->periodEnd = CarbonImmutable::parse('2026-02-01');

        $invoice = $formManager->createInvoiceFromDTO($dto);

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $violations = $validator->validateProperty($invoice, 'period');

        self::assertCount(1, $violations);
        self::assertSame('billing.period.inverted', $violations->get(0)->getMessageTemplate());
    }

    public function testOmittingBothDatesPassesTheEntityConstraint(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);

        $formManager = self::getContainer()->get(InvoiceFormManager::class);

        $dto = new InvoiceFormDTO();
        $dto->clientMode = InvoiceClientMode::Existing;
        $dto->client = $client;
        $dto->invoiceId = 'INV-PERIOD-3';
        $dto->discount = new Discount();

        $invoice = $formManager->createInvoiceFromDTO($dto);

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $violations = $validator->validateProperty($invoice, 'period');

        self::assertCount(0, $violations);
    }
}
