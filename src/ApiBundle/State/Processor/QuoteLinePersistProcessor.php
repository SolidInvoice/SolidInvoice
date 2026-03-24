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
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<Line, Line> */
final class QuoteLinePersistProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Line
    {
        assert($data instanceof Line);

        $quoteId = $uriVariables['quoteId'] ?? null;
        $quote = $this->quoteRepository->find($quoteId);

        if ($quote === null) {
            throw new NotFoundHttpException(sprintf('Quote "%s" not found.', $quoteId));
        }

        $data->setQuote($quote);

        $em = $this->registry->getManager();
        $em->persist($data);
        $em->flush();

        return $data;
    }
}
