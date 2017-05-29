<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Form\Handler;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Traits\SaveableTrait;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Money\Money;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\StateMachine;

abstract class AbstractInvoiceHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
{
    use SaveableTrait;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    public function __construct(StateMachine $stateMachine, PaymentRepository $paymentRepository, RouterInterface $router)
    {
        $this->stateMachine = $stateMachine;
        $this->router = $router;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, Options $options)
    {
        return $factory->create(InvoiceType::class, $options->get('invoice'), $options->get('form_options'));
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($invoice, FormRequest $form): ?Response
    {
        /* @var Invoice $invoice */
        $action = $form->getRequest()->request->get('save');

        $invoice->setBalance($invoice->getTotal());

        // @TODO: Recurring invoices should be handled better
        if ($invoice->isRecurring()) {
            $invoice->setStatus(Graph::STATUS_RECURRING);

            $firstInvoice = clone $invoice;
            $firstInvoice->setRecurring(false);
            $firstInvoice->setRecurringInfo(null);

            $this->stateMachine->apply($invoice, Graph::TRANSITION_NEW);

            $invoice = $firstInvoice;
        }

        if (!$invoice->getId()) {
            $this->stateMachine->apply($invoice, Graph::TRANSITION_NEW);
        }

        $totalPaid = $this->paymentRepository->getTotalPaidForInvoice($invoice);
        $invoice->setBalance($invoice->getTotal()->subtract(new Money($totalPaid, $invoice->getTotal()->getCurrency())));

        if ($action === Graph::STATUS_PENDING) {
            $this->stateMachine->apply($invoice, Graph::TRANSITION_ACCEPT);
        }

        $this->save($invoice);

        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'invoice.create.success';
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('invoice')
            ->setAllowedTypes('invoice', Invoice::class)
            ->setDefault('form_options', [])
            ->setAllowedTypes('form_options', 'array');
    }
}
