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

namespace SolidInvoice\QuoteBundle\Action\Grid;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Workflow\WorkflowInterface;

final class Archive implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private readonly QuoteRepository $repository,
        private readonly WorkflowInterface $quoteStateMachine
    ) {
    }

    public function __invoke(Request $request)
    {
        $data = $request->request->get('data');

        /** @var Quote[] $quotes */
        $quotes = $this->repository->findBy(['id' => $data]);

        foreach ($quotes as $quote) {
            if (! $this->quoteStateMachine->can($quote, Graph::TRANSITION_ARCHIVE)) {
                throw new InvalidTransitionException(Graph::TRANSITION_ARCHIVE);
            }

            $this->quoteStateMachine->apply($quote, Graph::TRANSITION_ARCHIVE);
        }

        return $this->json([]);
    }
}
