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

use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mockery as M;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceEditHandler;
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
 * @covers \SolidInvoice\InvoiceBundle\Form\Handler\InvoiceEditHandler
 */
final class InvoiceEditHandlerTest extends FormHandlerTestCase
{
    private Invoice $invoice;

    private Client $client;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MathException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->invoice = new Invoice();
        $this->invoice->setStatus(Graph::STATUS_DRAFT);
        $this->client = (new Client())->setName('Test')->setCurrencyCode('USD')->setCompany($this->company);
        $this->invoice->setClient($this->client);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(10);
        $this->invoice->setDiscount($discount);
        $this->invoice->setBalance(1000);
        $this->invoice->setInvoiceId('10');

        $this->em->persist($this->invoice);
        $this->em->flush();
    }

    public function getHandler(): InvoiceEditHandler
    {
        $dispatcher = new EventDispatcher();
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();

        $dispatcher->addSubscriber(new WorkFlowSubscriber($this->registry, $notification));
        $stateMachine = new StateMachine(
            new Definition(
                ['draft', 'pending'],
                [new Transition('accept', 'draft', 'pending')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'invoice'
        );
        $recurringStateMachine = new StateMachine(
            new Definition(
                ['draft', 'pending'],
                [new Transition('accept', 'draft', 'pending')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'invoice'
        );

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn('/invoices/' . $this->invoice->getId());

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send')
            ->zeroOrMoreTimes()
            ->with(M::type(InvoiceEmail::class));

        $handler = new InvoiceEditHandler(
            $stateMachine,
            $recurringStateMachine,
            $router,
            $mailer,
            M::mock(TotalCalculator::class),
        );
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    /**
     * @param Invoice $invoice
     * @throws NotSupported
     */
    protected function assertOnSuccess(?Response $response, FormRequest $form, $invoice): void
    {
        self::assertSame(Graph::STATUS_PENDING, $invoice->getStatus());
        self::assertSame(2000.0, $invoice->getDiscount()->getValue());
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
            'invoice' => $this->invoice,
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
                    'type' => Discount::TYPE_PERCENTAGE,
                ],
                'client' => [
                    'autocomplete' => $this->client->getId()->toString()
                ],
                'invoiceId' => '10',
                'invoiceDate' => new DateTimeImmutable(),
            ],
            'save' => 'pending',
        ];
    }
}
