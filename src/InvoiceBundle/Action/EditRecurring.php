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

namespace SolidInvoice\InvoiceBundle\Action;

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceType;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use function assert;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\EditRecurringTest
 */
final class EditRecurring extends AbstractController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $recurringInvoiceStateMachine,
        private readonly ManagerRegistry $doctrine,
        private readonly TotalCalculator $totalCalculator,
        private readonly FeatureGate $featureGate,
    ) {
    }

    /**
     * @throws MathException
     */
    public function __invoke(Request $request, RecurringInvoice $invoice): Response
    {
        if (! $this->featureGate->isEnabled(Feature::RecurringInvoices->value)) {
            return $this->render('@SolidInvoiceInvoice/Default/recurring_gated.html.twig');
        }

        $form = $this->formFactory->create(RecurringInvoiceType::class, $invoice, [
            'currency' => $invoice->getClient()->getCurrency(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            if ('publish' === $action && $this->recurringInvoiceStateMachine->can($invoice, Graph::TRANSITION_ACTIVATE)) {
                $this->recurringInvoiceStateMachine->apply($invoice, Graph::TRANSITION_ACTIVATE);
            }

            $this->doctrine->getManager()->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'invoice.edit.success');

            return new RedirectResponse($this->router->generate('_invoices_view_recurring', ['id' => $invoice->getId()]));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            $this->totalCalculator->calculateTotals($invoice);
        }

        return $this->render('@SolidInvoiceInvoice/Default/edit.html.twig', [
            'recurring' => true,
            'form' => $form->createView(),
            'invoice' => $invoice,
        ]);
    }
}
