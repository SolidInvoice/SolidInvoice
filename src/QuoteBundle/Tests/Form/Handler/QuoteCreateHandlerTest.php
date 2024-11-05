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

use Doctrine\ORM\Exception\NotSupported;
use Mockery as M;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber as InvoiceWorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Handler\QuoteCreateHandler;
use SolidInvoice\QuoteBundle\Form\Type\ItemType;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\QuoteBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\QuoteBundle\Mailer\QuoteMailer;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
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
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client();
        $this->client->setName('Test');
        $this->client->setCompany($this->company);
        $this->client->setCurrencyCode('USD');

        $this->em->persist($this->client);
    }

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
                'quoteId' => 'Q-100',
                'client' => $this->client->getId()->toString(),
            ],
        ];
    }

    /**
     * @param Quote $data
     * @throws NotSupported
     */
    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertSame(Graph::STATUS_DRAFT, $data->getStatus());
        self::assertSame('Q-100', $data->getQuoteId());
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
            'quote' => (new Quote())->setClient($this->client),
            'form_options' => [
                'currency' => new Currency('USD'),
            ],
        ];
    }

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        $systemConfig
            ->shouldReceive('get')
            ->once()
            ->with('quote/id_generation/strategy')
            ->andReturn('random_number');

        $systemConfig
            ->shouldReceive('get')
            ->once()
            ->with('quote/id_generation/prefix')
            ->andReturn('');

        $systemConfig
            ->shouldReceive('get')
            ->once()
            ->with('quote/id_generation/suffix')
            ->andReturn('');

        $randomNumberGenerator = M::mock(IdGeneratorInterface::class);
        $randomNumberGenerator
            ->shouldReceive('generate')
            ->once()
            ->withAnyArgs()
            ->andReturn('10');

        return [
            new PreloadedExtension(
                [
                    new HiddenMoneyType(),
                    new CurrencyType('en_US'),
                    new ContactDetailType(),
                    new QuoteType($systemConfig, $this->registry, new BillingIdGenerator(new ServiceLocator(['random_number' => static fn () => $randomNumberGenerator]), $systemConfig)),
                    new ItemType($this->registry),
                    new DiscountType($systemConfig),
                ],
                []
            ),
            new DoctrineOrmExtension($this->registry),
        ];
    }
}
