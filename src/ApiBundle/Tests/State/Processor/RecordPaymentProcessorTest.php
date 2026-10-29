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

namespace SolidInvoice\ApiBundle\Tests\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\DTO\RecordPaymentInput;
use SolidInvoice\ApiBundle\State\Processor\RecordPaymentProcessor;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\PaymentBundle\Manager\ManualPaymentManager;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(RecordPaymentProcessor::class)]
final class RecordPaymentProcessorTest extends TestCase
{
    /**
     * An invoice's `client` column is non-nullable, but {@see Invoice::getClient()} is still typed
     * nullable, and without a client the invoice has no currency to scale the amount by. That
     * must surface as a 422, not as HasFrozenCurrency::getCurrency()'s LogicException and a 500.
     */
    public function testRecordPaymentRejectsAnInvoiceWithNoClientAs422NotA500(): void
    {
        $data = new RecordPaymentInput();
        $data->amount = '10.00';

        $this->expectException(ValidationException::class);

        $this->processor(new Invoice())->process($data, $this->createStub(Operation::class), ['invoiceId' => 'invoice-id']);
    }

    public function testAnUnknownInvoiceIs404(): void
    {
        $data = new RecordPaymentInput();
        $data->amount = '10.00';

        $this->expectException(NotFoundHttpException::class);

        $this->processor(null)->process($data, $this->createStub(Operation::class), ['invoiceId' => 'invoice-id']);
    }

    private function processor(?Invoice $invoice): RecordPaymentProcessor
    {
        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 'invoice-id'])
            ->willReturn($invoice);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new RecordPaymentProcessor(
            $invoiceRepository,
            new ManualPaymentManager(
                $this->createStub(PaymentMethodRepository::class),
                $this->createStub(EntityManagerInterface::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(WorkflowInterface::class),
                $translator,
            ),
            new CurrencyScale(),
            $translator,
        );
    }
}
