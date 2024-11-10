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

namespace SolidInvoice\QuoteBundle\Form\Handler;

use Brick\Math\Exception\MathException;
use Exception;
use Generator;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidWorx\FormHandler\FormHandlerFailInterface;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractQuoteHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver, FormHandlerFailInterface
{
    use SaveableTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $quoteStateMachine,
        private readonly TotalCalculator $totalCalculator,
    ) {
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(QuoteType::class, $options->get('quote'), $options->get('form_options'));
    }

    /**
     * @throws Exception
     */
    public function onSuccess(FormRequest $form, $quote): ?Response
    {
        /** @var Quote $quote */
        $action = $form->getRequest()->request->get('save');

        if (! $quote->getId() instanceof Ulid) {
            $this->quoteStateMachine->apply($quote, Graph::TRANSITION_NEW);
        }

        if (Graph::STATUS_PENDING === $action) {
            $this->quoteStateMachine->apply($quote, Graph::TRANSITION_SEND);
        }

        if ('publish' === $action) {
            $this->quoteStateMachine->apply($quote, Graph::TRANSITION_PUBLISH);
        }

        $this->save($quote);

        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'quote.action.create.success';
            }
        };
    }

    /**
     * @param FormErrorIterator<FormError> $errors
     * @throws MathException
     */
    public function onFail(FormRequest $formRequest, FormErrorIterator $errors, $data = null): ?Response
    {
        $invoice = $formRequest->getOptions()->get('quote');

        assert($invoice instanceof Quote);

        $this->totalCalculator->calculateTotals($invoice);

        return null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('quote')
            ->setAllowedTypes('quote', Quote::class)
            ->setDefault('form_options', [])
            ->setAllowedTypes('form_options', 'array');
    }
}
