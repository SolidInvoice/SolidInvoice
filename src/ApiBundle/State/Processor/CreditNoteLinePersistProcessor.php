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
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\CreditNoteLine;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function sprintf;

/** @implements ProcessorInterface<CreditNoteLine, CreditNoteLine> */
final readonly class CreditNoteLinePersistProcessor implements ProcessorInterface
{
    public function __construct(
        private CreditNoteRepository $creditNoteRepository,
        private ManagerRegistry $registry,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CreditNoteLine
    {
        assert($data instanceof CreditNoteLine);

        $creditNoteId = $uriVariables['creditNoteId'] ?? null;
        $creditNote = $this->creditNoteRepository->find($creditNoteId);

        if ($creditNote === null) {
            throw new NotFoundHttpException(sprintf('Credit note "%s" not found.', $creditNoteId));
        }

        // addLine(), so a line posted without a position lands after the credit note's existing ones.
        $creditNote->addLine($data);

        $em = $this->registry->getManager();
        $em->persist($data);
        $em->flush();

        return $data;
    }
}
