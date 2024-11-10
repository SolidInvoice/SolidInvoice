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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber as InvoiceWorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Handler\QuoteEditHandler;
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
 * @covers \SolidInvoice\QuoteBundle\Form\Handler\QuoteEditHandler
 */
final class QuoteEditHandlerTest extends FormHandlerTestCase
{
    private Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $client = (new Client())
            ->setName($this->faker->name)
            ->setCurrencyCode('USD')
            ->setCompany($this->company)
        ;

        $this->quote = new Quote();
        $this->quote->setStatus(Graph::STATUS_DRAFT);
        $this->quote->setClient($client);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(1);
        $this->quote->setDiscount($discount);
        $this->quote->setQuoteId('10');

        $this->em->persist($client);
        $this->em->persist($this->quote);
        $this->em->flush();
    }

    public function getHandler(): QuoteEditHandler
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

        $stateMachine = new StateMachine(
            new Definition(
                ['draft', 'pending'],
                [new Transition('send', 'draft', 'pending')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'quote'
        );

        $dispatcher = new EventDispatcher();
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();

        $dispatcher->addSubscriber(
            new WorkFlowSubscriber(
                $this->registry,
                M::mock(InvoiceManager::class),
                $invoiceStateMachine,
                $notification,
                new QuoteMailer($stateMachine, M::mock(MailerInterface::class), $notification)
            )
        );

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn('/quotes/1');

        $handler = new QuoteEditHandler($router, $stateMachine, $this->createMock(TotalCalculator::class));
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    /**
     * @param Quote $quote
     */
    protected function assertOnSuccess(?Response $response, FormRequest $form, $quote): void
    {
        self::assertSame(Graph::STATUS_PENDING, $quote->getStatus());
        self::assertSame(2000.0, $quote->getDiscount()->getValue());
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
    public function getFormData(): array
    {
        return [
            'quote' => [
                'discount' => [
                    'value' => 20,
                    'type' => Discount::TYPE_PERCENTAGE,
                ],
                'client' => $this->quote->getClient()->getId()->toString(),
                'quoteId' => '10',
            ],
            'save' => 'pending',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHandlerOptions(): array
    {
        return [
            'quote' => $this->quote,
            'form_options' => [
                'currency' => new Currency('USD'),
            ],
        ];
    }
}
