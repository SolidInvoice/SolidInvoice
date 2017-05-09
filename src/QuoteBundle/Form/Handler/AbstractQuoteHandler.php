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

namespace CSBill\QuoteBundle\Form\Handler;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Traits\SaveableTrait;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Event\QuoteEvent;
use CSBill\QuoteBundle\Event\QuoteEvents;
use CSBill\QuoteBundle\Form\Type\QuoteType;
use CSBill\QuoteBundle\Model\Graph;
use Finite\Factory\FactoryInterface;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractQuoteHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface
{
    use SaveableTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(EventDispatcherInterface $eventDispatcher, RouterInterface $router, FactoryInterface $factory)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->factory = $factory;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, ...$options)
    {
        return $factory->create(QuoteType::class, $options[0], $options[1] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($quote, FormRequest $form): ?Response
    {
        /* @var Quote $quote */
        $action = $form->getRequest()->request->get('save');
        $this->saveQuote($quote, $action);

        $this->eventDispatcher->dispatch(QuoteEvents::QUOTE_POST_CREATE, new QuoteEvent($quote));

        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'quote.action.create.success';
            }
        };
    }

    private function saveQuote(Quote $quote, $action = null)
    {
        $finite = $this->factory->get($quote, Graph::GRAPH);

        if (!$quote->getId()) {
            $this->eventDispatcher->dispatch(QuoteEvents::QUOTE_PRE_CREATE, new QuoteEvent($quote));
        }

        if ($action === Graph::STATUS_PENDING) {
            $this->eventDispatcher->dispatch(QuoteEvents::QUOTE_PRE_SEND, new QuoteEvent($quote));
            $finite->apply(Graph::TRANSITION_SEND);
            $this->save($quote);
            $this->eventDispatcher->dispatch(QuoteEvents::QUOTE_POST_SEND, new QuoteEvent($quote));
        } else {
            if (!$quote->getId()) {
                $finite->apply(Graph::TRANSITION_NEW);
            }

            $this->save($quote);
        }
    }
}
