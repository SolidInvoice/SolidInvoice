<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Form\Handler;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceType;
use SolidInvoice\InvoiceBundle\Model\Graph;
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

    public function __construct(StateMachine $stateMachine, RouterInterface $router)
    {
        $this->stateMachine = $stateMachine;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        $formType = $options->get('recurring') ? RecurringInvoiceType::class : InvoiceType::class;

        return $factory->create($formType, $options->get('invoice'), $options->get('form_options'));
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess(FormRequest $form, $invoice): ?Response
    {
        /* @var Invoice $invoice */
        $action = $form->getRequest()->request->get('save');
        $isRecurring = $form->getOptions()->get('recurring');

        if (!$invoice->getId()) {
            $this->stateMachine->apply($invoice, Graph::TRANSITION_NEW);
        }

        if (Graph::STATUS_PENDING === $action) {
            $this->stateMachine->apply($invoice, $isRecurring ? Graph::TRANSITION_ACTIVATE : Graph::TRANSITION_ACCEPT);
        }

        $this->save($invoice);
        $route = $this->router->generate($isRecurring ? '_invoices_view_recurring' : '_invoices_view', ['id' => $invoice->getId()]);

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
        $resolver
            ->setRequired('invoice')
            ->setAllowedTypes('invoice', [Invoice::class, RecurringInvoice::class])
            ->setDefault('form_options', [])
            ->setDefault('recurring', false)
            ->setAllowedTypes('form_options', 'array')
            ->setAllowedTypes('recurring', 'boolean');
    }
}
