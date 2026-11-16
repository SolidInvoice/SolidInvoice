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
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\DTO\RecordPaymentInput;
use SolidInvoice\ApiBundle\State\Processor\RecordPaymentProcessor;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(RecordPaymentProcessor::class)]
final class RecordPaymentProcessorTest extends TestCase
{
    /**
     * An invoice's `client` column is non-nullable, but {@see Invoice::getClient()} is
     * still typed nullable, so the processor must guard the no-client case explicitly rather
     * than let {@see \SolidInvoice\CoreBundle\Traits\Entity\HasFrozenCurrency::getCurrency()}'s
     * `LogicException` surface as an unhandled 500.
     */
    public function testRecordPaymentRejectsAnInvoiceWithNoClientAs422NotA500(): void
    {
        $invoice = new Invoice();

        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 'invoice-id'])
            ->willReturn($invoice);

        $paymentMethodRepository = $this->createMock(PaymentMethodRepository::class);
        $paymentMethodRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['factoryName' => PaymentMethod::FACTORY_OFFLINE])
            ->willReturn(new PaymentMethod());

        $processor = new RecordPaymentProcessor(
            $invoiceRepository,
            $paymentMethodRepository,
            $this->createStub(ManagerRegistry::class),
            $this->createStub(WorkflowInterface::class),
        );

        $data = new RecordPaymentInput();
        $data->amount = 1000;
        $data->currency = 'USD';

        $this->expectException(UnprocessableEntityHttpException::class);

        $processor->process($data, $this->createStub(Operation::class), ['invoiceId' => 'invoice-id']);
    }
}
