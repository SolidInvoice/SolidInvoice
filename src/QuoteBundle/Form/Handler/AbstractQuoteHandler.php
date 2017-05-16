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
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\StateMachine;

abstract class AbstractQuoteHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface
{
    use SaveableTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(EventDispatcherInterface $eventDispatcher, RouterInterface $router, StateMachine $stateMachine)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->stateMachine = $stateMachine;
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

        $this->eventDispatcher->dispatch(QuoteEvents::QUOTE_PRE_CREATE, new QuoteEvent($quote));

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
        if (!$quote->getId()) {
            $this->stateMachine->apply($quote, Graph::TRANSITION_NEW);
        }

        if (Graph::STATUS_PENDING === $action) {
            $this->stateMachine->apply($quote, Graph::TRANSITION_SEND);
        }

        $this->save($quote);
    }
}
