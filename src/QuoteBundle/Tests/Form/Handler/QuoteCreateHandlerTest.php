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

namespace CSBill\QuoteBundle\Tests\Form\Handler;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Templating\Template;
use CSBill\FormBundle\Test\FormHandlerTestCase;
use CSBill\MoneyBundle\Entity\Money;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Form\Handler\QuoteCreateHandler;
use CSBill\QuoteBundle\Model\Graph;
use Finite\Factory\FactoryInterface;
use Finite\StateMachine\StateMachineInterface;
use Mockery as M;
use Money\Currency;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class QuoteCreateHandlerTest extends FormHandlerTestCase
{
    public function getHandler()
    {
        $stateMachine = M::mock(StateMachineInterface::class);
        $stateMachine->shouldReceive('can')
            ->once()
            ->with(Graph::TRANSITION_NEW)
            ->andReturn(true);

        $stateMachine->shouldReceive('apply')
            ->with(Graph::TRANSITION_NEW);

        $factory = M::mock(FactoryInterface::class);
        $factory->shouldReceive('get')
            ->once()
            ->andReturn($stateMachine);

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->once()
            ->with('_quotes_view', ['id' => 1])
            ->andReturn('/quotes/1');

        $handler = new QuoteCreateHandler(new EventDispatcher(), $router, $factory);
        $handler->setDoctrine($this->registry);

        Money::setBaseCurrency('USD');

        return $handler;
    }

    public function getFormData(): array
    {
        return [
            'quote' => [
                'discount' => 20,
            ],
        ];
    }

    protected function assertOnSuccess(?Response $response, $quote, FormRequest $form)
    {
        /* @var Quote $quote */

        $this->assertSame(Graph::STATUS_DRAFT, $quote->getStatus());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(FlashResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
        $this->assertCount(1, $this->em->getRepository('CSBillQuoteBundle:Quote')->findAll());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    protected function getHandlerOptions(): array
    {
        return [
            (new Quote())->setStatus(Graph::STATUS_DRAFT),
            [
                'currency' => new Currency('USD'),
            ],
        ];
    }

    protected function getEntityNamespaces(): array
    {
        return [
            'CSBillClientBundle' => 'CSBill\ClientBundle\Entity',
            'CSBillQuoteBundle' => 'CSBill\QuoteBundle\Entity',
            'CSBillPaymentBundle' => 'CSBill\PaymentBundle\Entity',
            'CSBillTaxBundle' => 'CSBill\TaxBundle\Entity',
        ];
    }

    protected function getEntities(): array
    {
        return [
            'CSBillClientBundle:Client',
            'CSBillQuoteBundle:Quote',
            'CSBillTaxBundle:Tax',
        ];
    }
}
