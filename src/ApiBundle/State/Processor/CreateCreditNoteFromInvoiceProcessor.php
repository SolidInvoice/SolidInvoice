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
use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function sprintf;

/** @implements ProcessorInterface<CreditNote, CreditNote> */
final readonly class CreateCreditNoteFromInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private ManagerRegistry $registry,
        private ValidatorInterface $validator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CreditNote
    {
        assert($data instanceof CreditNote);

        $invoiceId = $uriVariables['invoiceId'] ?? null;
        $invoice = $this->invoiceRepository->find($invoiceId);

        if ($invoice === null) {
            throw new NotFoundHttpException(sprintf('Invoice "%s" not found.', $invoiceId));
        }

        $data->setInvoice($invoice);
        $data->setClient($invoice->getClient());
        // CompanyListener only sets this on flush. The operation skips its own validation for
        // the same reason (see the `validate: false` comment on the operation), so it has to
        // be set here, before the manual validate() call below.
        $data->setCompany($invoice->getCompany());

        $violations = $this->validator->validate($data);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $em = $this->registry->getManager();
        $em->persist($data);
        $em->flush();

        return $data;
    }
}
