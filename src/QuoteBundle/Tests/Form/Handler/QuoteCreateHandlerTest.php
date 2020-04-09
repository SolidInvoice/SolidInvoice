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

namespace SolidInvoice\QuoteBundle\Tests\Form\Handler;

use Mockery as M;
use Money\Currency;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber as InvoiceWorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\MoneyBundle\Entity\Money;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Handler\QuoteCreateHandler;
use SolidInvoice\QuoteBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class QuoteCreateHandlerTest extends FormHandlerTestCase
{
    public function getHandler()
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

        $dispatcher->addSubscriber(new WorkFlowSubscriber($this->registry, M::mock(InvoiceManager::class), $invoiceStateMachine, $notification));
        $stateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'quote'
        );

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn('/quotes/1');

        $handler = new QuoteCreateHandler($router, $stateMachine);
        $handler->setDoctrine($this->registry);

        Money::setBaseCurrency('USD');

        return $handler;
    }

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

    protected function assertOnSuccess(?Response $response, FormRequest $form, $quote): void
    {
        /* @var Quote $quote */

        $this->assertSame(Graph::STATUS_DRAFT, $quote->getStatus());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(FlashResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
        $this->assertCount(1, $this->em->getRepository(Quote::class)->findAll());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

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
