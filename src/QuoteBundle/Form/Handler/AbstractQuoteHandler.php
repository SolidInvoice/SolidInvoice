<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Form\Handler;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\StateMachine;

abstract class AbstractQuoteHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
{
    use SaveableTrait;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router, StateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(QuoteType::class, $options->get('quote'), $options->get('form_options'));
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess(FormRequest $form, $quote): ?Response
    {
        /* @var Quote $quote */
        $action = $form->getRequest()->request->get('save');

        if (!$quote->getId()) {
            $this->stateMachine->apply($quote, Graph::TRANSITION_NEW);
        }

        if (Graph::STATUS_PENDING === $action) {
            $this->stateMachine->apply($quote, Graph::TRANSITION_SEND);
        }

        $this->save($quote);

        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'quote.action.create.success';
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('quote')
            ->setAllowedTypes('quote', Quote::class)
            ->setDefault('form_options', [])
            ->setAllowedTypes('form_options', 'array');
    }
}
