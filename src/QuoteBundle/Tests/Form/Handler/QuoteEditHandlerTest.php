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

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber as InvoiceWorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\MoneyBundle\Entity\Money;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Handler\QuoteEditHandler;
use SolidInvoice\QuoteBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\QuoteBundle\Model\Graph;
use Mockery as M;
use Money\Currency;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class QuoteEditHandlerTest extends FormHandlerTestCase
{
    private $quote;

    protected function setUp()
    {
        parent::setUp();

        $this->quote = new Quote();
        $this->quote->setStatus(Graph::STATUS_DRAFT);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(1);
        $this->quote->setDiscount($discount);

        $this->em->persist($this->quote);
        $this->em->flush();

        Money::setBaseCurrency('USD');
    }

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
            new SingleStateMarkingStore('status'),
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
                ['draft', 'pending'],
                [new Transition('send', 'draft', 'pending')]
            ),
            new SingleStateMarkingStore('status'),
            $dispatcher,
            'quote'
        );

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->with('_quotes_view', ['id' => 1])
            ->andReturn('/quotes/1');

        $handler = new QuoteEditHandler($router, $stateMachine);
        $handler->setDoctrine($this->registry);

        Money::setBaseCurrency('USD');

        return $handler;
    }

    protected function assertOnSuccess(?Response $response, $quote, FormRequest $form)
    {
        /* @var Quote $quote */

        $this->assertSame(Graph::STATUS_PENDING, $quote->getStatus());
        $this->assertSame(20.0, $quote->getDiscount()->getValue());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(FlashResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
        $this->assertCount(1, $this->em->getRepository(Quote::class)->findAll());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    public function getFormData(): array
    {
        return [
            'quote' => [
                'discount' => [
                    'value' => 20,
                    'type' => Discount::TYPE_PERCENTAGE,
                ],
            ],
            'save' => 'pending',
        ];
    }

    protected function getHandlerOptions(): array
    {
        return [
            'quote' => $this->quote,
            'form_options' => [
                'currency' => new Currency('USD'),
            ],
        ];
    }

    protected function getEntityNamespaces(): array
    {
        return [
            'SolidInvoiceClientBundle' => 'SolidInvoice\ClientBundle\Entity',
            'SolidInvoiceQuoteBundle' => 'SolidInvoice\QuoteBundle\Entity',
            'SolidInvoicePaymentBundle' => 'SolidInvoice\PaymentBundle\Entity',
            'SolidInvoiceTaxBundle' => 'SolidInvoice\TaxBundle\Entity',
        ];
    }

    protected function getEntities(): array
    {
        return [
            Client::class,
            Quote::class,
            Tax::class,
        ];
    }
}
