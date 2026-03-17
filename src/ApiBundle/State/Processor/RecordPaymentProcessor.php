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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ApiBundle\DTO\RecordPaymentInput;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements ProcessorInterface<RecordPaymentInput, Payment> */
final class RecordPaymentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly PaymentMethodRepository $paymentMethodRepository,
        private readonly ManagerRegistry $registry,
        private readonly WorkflowInterface $invoiceStateMachine,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Payment
    {
        assert($data instanceof RecordPaymentInput);

        $invoice = $this->invoiceRepository->find($uriVariables['invoiceId']);

        if ($invoice === null) {
            throw new NotFoundHttpException(sprintf('Invoice "%s" not found.', $uriVariables['invoiceId']));
        }

        $offlineMethod = $this->paymentMethodRepository->findOneBy(['factoryName' => 'offline']);

        if (! $offlineMethod instanceof PaymentMethod) {
            throw new ServiceUnavailableHttpException(null, 'Offline payment method is not configured.');
        }

        $payment = new Payment();
        $payment->setTotalAmount($data->amount);
        $payment->setCurrencyCode($data->currency);
        $payment->setReference($data->reference);
        $payment->setNotes($data->notes);
        $payment->setMethod($offlineMethod);
        $payment->setInvoice($invoice);
        $client = $invoice->getClient();
        if ($client !== null) {
            $payment->setClient($client);
        }
        $payment->setStatus(PaymentStatus::Captured);
        $payment->setCompleted(new DateTimeImmutable());
        $payment->setCompany($invoice->getCompany());

        $em = $this->registry->getManager();
        $em->persist($payment);

        if ($this->invoiceStateMachine->can($invoice, Graph::TRANSITION_PAY)) {
            $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_PAY);
        }

        $em->flush();

        return $payment;
    }
}
