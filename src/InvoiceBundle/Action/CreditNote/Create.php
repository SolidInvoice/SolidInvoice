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

namespace SolidInvoice\InvoiceBundle\Action\CreditNote;

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\CreditNoteLine;
use SolidInvoice\InvoiceBundle\Form\Type\CreditNoteType;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use function assert;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\CreditNote\CreateTest
 */
final readonly class Create
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private ClientRepository $clientRepository,
        private WorkflowInterface $creditNoteStateMachine,
        private RouterInterface $router,
        private ManagerRegistry $doctrine,
        private TotalCalculator $totalCalculator,
    ) {
    }

    /**
     * @throws MathException
     */
    #[Template('@SolidInvoiceInvoice/CreditNote/create.html.twig')]
    public function __invoke(Request $request, ?Client $client = null): array | Response
    {
        $creditNote = new CreditNote();
        $creditNote->addLine(new CreditNoteLine());

        if (1 === $this->clientRepository->getTotalClients() && ! $client instanceof Client) {
            $client = $this->clientRepository->findOneBy([]);
        }

        $creditNote->setClient($client);

        $formOptions = $client instanceof Client ? ['currency' => $client->getCurrency()] : [];
        $form = $this->formFactory->create(CreditNoteType::class, $creditNote, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            if ('issue' === $action && $this->creditNoteStateMachine->can($creditNote, Graph::TRANSITION_ISSUE)) {
                $this->creditNoteStateMachine->apply($creditNote, Graph::TRANSITION_ISSUE);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($creditNote);
            $entityManager->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'credit_note.create.success');

            return new RedirectResponse($this->router->generate('_credit_notes_view', ['id' => $creditNote->getId()]));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            $this->totalCalculator->calculateTotals($creditNote);
        }

        return [
            'form' => $form->createView(),
            'creditNote' => $creditNote,
            'isEdit' => false,
        ];
    }
}
