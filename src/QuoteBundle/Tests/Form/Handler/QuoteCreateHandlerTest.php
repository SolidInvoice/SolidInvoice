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

namespace SolidInvoice\QuoteBundle\Tests\Form\Handler;

use Mockery as M;
use Money\Currency;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber as InvoiceWorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Handler\QuoteCreateHandler;
use SolidInvoice\QuoteBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\QuoteBundle\Mailer\QuoteMailer;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use function iterator_to_array;

/**
 * @covers \SolidInvoice\QuoteBundle\Form\Handler\QuoteCreateHandler
 */
final class QuoteCreateHandlerTest extends FormHandlerTestCase
{
    public function getHandler(): QuoteCreateHandler
    {
        $dispatcher = new EventDispatcher();
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();

        $dispatcher->addSubscriber(new InvoiceWorkFlowSubscriber($this->registry, $notification));
        $invoiceStateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'invoice'
        );

        $dispatcher = new EventDispatcher();
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();

        $stateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'quote'
        );

        $dispatcher->addSubscriber(
            new WorkFlowSubscriber(
                $this->registry,
                M::mock(InvoiceManager::class),
                $invoiceStateMachine,
                $notification,
                new QuoteMailer($stateMachine, M::mock(MailerInterface::class), $notification),
            )
        );

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn('/quotes/1');

        $handler = new QuoteCreateHandler($router, $stateMachine);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormData(): array
    {
        return [
            'quote' => [
                'discount' => [
                    'value' => 20,
                    'type' => 'percentage',
                ],
            ],
        ];
    }

    /**
     * @param Quote $data
     */
    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertSame(Graph::STATUS_DRAFT, $data->getStatus());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertCount(1, iterator_to_array($response->getFlash()));
        self::assertCount(1, $this->em->getRepository(Quote::class)->findAll());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHandlerOptions(): array
    {
        return [
            'quote' => new Quote(),
            'form_options' => [
                'currency' => new Currency('USD'),
            ],
        ];
    }
}
