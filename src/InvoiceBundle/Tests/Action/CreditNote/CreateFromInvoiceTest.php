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

namespace SolidInvoice\InvoiceBundle\Tests\Action\CreditNote;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\Container\ContainerInterface;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Action\CreditNote\CreateFromInvoice;
use SolidInvoice\InvoiceBundle\DTO\CreditFromInvoiceData;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditFromInvoiceMode;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The "Credit this invoice" full-or-partial picker, per `design` §8 on SOL-69. Builds the
 * action directly with a stubbed {@see FormFactoryInterface} so the real {@see ValidatorInterface}
 * and {@see CreditNoteRepository} from the container still exercise the over-crediting cap and
 * the multi-tenancy validator.
 */
#[CoversClass(CreateFromInvoice::class)]
#[Group('functional')]
final class CreateFromInvoiceTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testFullCreditUsesTheRemainingBalance(): void
    {
        $container = self::getContainer();
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'total' => 10000,
            'invoiceId' => 'INV-0001',
        ]);

        $action = $this->buildAction($container, static function (CreditFromInvoiceData $data): void {
            $data->mode = CreditFromInvoiceMode::Full;
        });

        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get('request_stack')->push($request);

        $response = $action($request, $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);

        $creditNote = $container->get(CreditNoteRepository::class)->findOneBy(['invoice' => $invoice]);
        self::assertInstanceOf(CreditNote::class, $creditNote);
        self::assertTrue($creditNote->getTotal()->isEqualTo(10000));
    }

    public function testPartialCreditUsesTheEnteredAmount(): void
    {
        $container = self::getContainer();
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'total' => 10000,
            'invoiceId' => 'INV-0002',
        ]);

        $action = $this->buildAction($container, static function (CreditFromInvoiceData $data): void {
            $data->mode = CreditFromInvoiceMode::Partial;
            $data->amount = '2500';
        });

        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get('request_stack')->push($request);

        $response = $action($request, $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);

        $creditNote = $container->get(CreditNoteRepository::class)->findOneBy(['invoice' => $invoice]);
        self::assertInstanceOf(CreditNote::class, $creditNote);
        self::assertTrue($creditNote->getTotal()->isEqualTo(2500));
    }

    /**
     * The multi-tenancy regression: {@see \SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteInvoiceBelongsToSameCompany}
     * must reject a credit note built against another company's invoice even through this
     * form-free flow, mirroring {@see \SolidInvoice\InvoiceBundle\Tests\Functional\CreditNoteBalanceTest}.
     */
    public function testCrossCompanyInvoiceIsRejected(): void
    {
        $container = self::getContainer();
        $otherCompany = CompanyFactory::createOne(['name' => 'Another Company']);

        $filters = $container->get('doctrine')->getManager()->getFilters();
        $filters->disable('company');
        $otherClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignInvoice = InvoiceFactory::createOne([
            'company' => $otherCompany,
            'client' => $otherClient,
            'status' => InvoiceStatus::Pending,
            'total' => 5000,
            'invoiceId' => 'INV-FOREIGN-0001',
        ]);
        $filters->enable('company');

        $action = $this->buildAction($container, static function (CreditFromInvoiceData $data): void {
            $data->mode = CreditFromInvoiceMode::Full;
        });

        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get('request_stack')->push($request);

        $response = $action($request, $foreignInvoice);

        self::assertIsArray($response, 'A validation violation re-renders the form instead of redirecting.');

        $creditNote = $container->get(CreditNoteRepository::class)->findOneBy(['invoice' => $foreignInvoice]);
        self::assertNull($creditNote, 'No credit note should be persisted when it fails validation.');
    }

    /**
     * $fillData runs against the action's own {@see CreditFromInvoiceData} instance — the one
     * it builds and passes to the (stubbed) form factory — simulating what a real
     * `handleRequest()` would have written onto it from the submitted payload.
     */
    private function buildAction(ContainerInterface $container, callable $fillData): CreateFromInvoice
    {
        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('handleRequest');
        $form->method('addError');

        $formFactory = $this->createStub(FormFactoryInterface::class);
        $formFactory->method('create')->willReturnCallback(static function (string $type, object $data, array $options) use ($form, $fillData): FormInterface {
            $fillData($data);

            return $form;
        });

        return new CreateFromInvoice(
            $formFactory,
            $container->get(RouterInterface::class),
            $container->get('doctrine'),
            $container->get(TotalCalculator::class),
            $container->get(CreditNoteRepository::class),
            $container->get(ValidatorInterface::class),
            $container->get('translator'),
        );
    }
}
