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

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\DTO\CreditFromInvoiceData;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\CreditNoteLine;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\CreditFromInvoiceMode;
use SolidInvoice\InvoiceBundle\Form\Type\CreditFromInvoiceType;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function assert;

/**
 * The "Credit this invoice" flow — a full-or-partial picker rather than the full line
 * editor, per `design` §8 on SOL-69. Always creates a draft; issuing is a separate step
 * via {@see Transition}, so the amount can be reviewed first.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\CreditNote\CreateFromInvoiceTest
 */
final readonly class CreateFromInvoice
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private ManagerRegistry $doctrine,
        private TotalCalculator $totalCalculator,
        private CreditNoteRepository $creditNoteRepository,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws MathException
     */
    #[Template('@SolidInvoiceInvoice/CreditNote/create_from_invoice.html.twig')]
    public function __invoke(Request $request, Invoice $invoice): array | Response
    {
        $data = new CreditFromInvoiceData();
        $remaining = $this->remainingCreditableAmount($invoice);

        $form = $this->formFactory->create(CreditFromInvoiceType::class, $data, [
            'currency' => $invoice->getCurrency(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $amount = CreditFromInvoiceMode::Full === $data->mode ? $remaining : BigNumber::of($data->amount);

            $creditNote = new CreditNote();
            $creditNote->setClient($invoice->getClient());
            $creditNote->setInvoice($invoice);
            $creditNote->setReason($data->reason);
            $creditNote->addLine(
                (new CreditNoteLine())
                    ->setName($this->translator->trans('credit_note.create_from_invoice.line_name', ['%invoice%' => $invoice->getInvoiceId()]))
                    ->setPrice((string) $amount)
                    ->setQty('1')
            );

            $violations = $this->validator->validate($creditNote);

            if (0 === $violations->count()) {
                $this->totalCalculator->calculateTotals($creditNote);

                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($creditNote);
                $entityManager->flush();

                $session = $request->getSession();
                assert($session instanceof Session);
                $session->getFlashBag()->add('success', 'credit_note.create.success');

                return new RedirectResponse($this->router->generate('_credit_notes_view', ['id' => $creditNote->getId()]));
            }

            foreach ($violations as $violation) {
                $form->addError(new FormError((string) $violation->getMessage()));
            }
        }

        return [
            'form' => $form->createView(),
            'invoice' => $invoice,
            'remaining' => $remaining,
        ];
    }

    /**
     * @throws MathException
     */
    private function remainingCreditableAmount(Invoice $invoice): BigNumber
    {
        $credited = $this->creditNoteRepository->getTotalCreditedForInvoice($invoice);
        $remaining = $invoice->getTotal()->toBigDecimal()->minus($credited);

        return $remaining->isNegative() ? BigDecimal::zero() : $remaining;
    }
}
