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

namespace SolidInvoice\InvoiceBundle\Tests\Form\Handler;

use Mockery as M;
use Money\Currency;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceCreateHandler;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
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
 * @covers \SolidInvoice\InvoiceBundle\Form\Handler\InvoiceCreateHandler
 */
final class InvoiceCreateHandlerTest extends FormHandlerTestCase
{
    public function getHandler(): InvoiceCreateHandler
    {
        $dispatcher = new EventDispatcher();
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();

        $dispatcher->addSubscriber(new WorkFlowSubscriber($this->registry, $notification));
        $stateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'invoice'
        );

        $recurringStateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'recurring_invoice'
        );

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn('/invoices/1');

        $handler = new InvoiceCreateHandler($stateMachine, $recurringStateMachine, $router, M::mock(MailerInterface::class));
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    /**
     * @param Invoice $invoice
     */
    protected function assertOnSuccess(?Response $response, FormRequest $form, $invoice): void
    {
        self::assertSame(Graph::STATUS_DRAFT, $invoice->getStatus());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertCount(1, iterator_to_array($response->getFlash()));
        self::assertCount(1, $this->em->getRepository(Invoice::class)->findAll());
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
            'invoice' => new Invoice(),
            'form_options' => [
                'currency' => new Currency('USD'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormData(): array
    {
        return [
            'invoice' => [
                'discount' => [
                    'value' => 20,
                    'type' => 'percentage',
                ],
            ],
        ];
    }
}
