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

namespace CSBill\QuoteBundle\Action\Grid;

use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Exception\InvalidTransitionException;
use CSBill\QuoteBundle\Manager\QuoteManager;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Repository\QuoteRepository;
use Finite\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class Archive
{
    use JsonTrait;

    /**
     * @var QuoteRepository
     */
    private $repository;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var QuoteManager
     */
    private $manager;

    public function __construct(QuoteRepository $repository, QuoteManager $manager, FactoryInterface $factory)
    {
        $this->repository = $repository;
        $this->factory = $factory;
        $this->manager = $manager;
    }

    public function __invoke(Request $request)
    {
        $data = $request->request->get('data');

        /** @var Quote[] $quotes */
        $quotes = $this->repository->findBy(['id' => $data]);

        foreach ($quotes as $quote) {
            if (!$this->factory->get($quote, Graph::GRAPH)->can('archive')) {
                throw new InvalidTransitionException('archive');
            }

            $this->manager->archive($quote);
        }

        return $this->json([]);
    }
}
