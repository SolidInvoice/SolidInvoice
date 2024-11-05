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

use DateTimeImmutable;
use Doctrine\ORM\Exception\NotSupported;
use Mockery as M;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\ClientAutocompleteType;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceCreateHandler;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType as InvoiceItemType;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use function iterator_to_array;

/**
 * @covers \SolidInvoice\InvoiceBundle\Form\Handler\InvoiceCreateHandler
 */
final class InvoiceCreateHandlerTest extends FormHandlerTestCase
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

        $handler = new InvoiceCreateHandler(
            $stateMachine,
            $recurringStateMachine,
            $router,
            M::mock(MailerInterface::class),
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
        self::assertSame(Graph::STATUS_DRAFT, $invoice->getStatus());
        self::assertSame('10', $invoice->getInvoiceId());
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
            'invoice' => (new Invoice())
                ->setClient($this->client)
                ->setStatus(Graph::STATUS_NEW),
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
                'invoiceId' => '10',
                'invoiceDate' => new DateTimeImmutable(),
                'client' => $this->client->getId()->toString(),
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
            ->with('invoice/id_generation/strategy')
            ->andReturn('random_number');

        $systemConfig
            ->shouldReceive('get')
            ->once()
            ->with('invoice/id_generation/prefix')
            ->andReturn('');

        $systemConfig
            ->shouldReceive('get')
            ->once()
            ->with('invoice/id_generation/suffix')
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
                    new InvoiceType($systemConfig, $this->registry, new BillingIdGenerator(new ServiceLocator(['random_number' => static fn () => $randomNumberGenerator]), $systemConfig)),
                    new InvoiceItemType($this->registry),
                    new DiscountType($systemConfig),
                    new BaseEntityAutocompleteType($this->createMock(UrlGeneratorInterface::class)),
                    new ClientAutocompleteType(),
                ],
                [
                    ChoiceType::class => [
                        new AutocompleteChoiceTypeExtension(new ChecksumCalculator('abc')),
                    ],
                    TextType::class => [
                        new AutocompleteChoiceTypeExtension(new ChecksumCalculator('abc')),
                    ],
                ]
            ),
            new DoctrineOrmExtension($this->registry),
        ];
    }
}
