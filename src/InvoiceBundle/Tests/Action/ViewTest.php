<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Tests\Action;

use DateTimeImmutable;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InvoiceBundle\Action\View;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

final class ViewTest extends KernelTestCase
{
    use MatchesSnapshots;
    use Factories;

    /**
     * @dataProvider invoiceStatusProvider
     */
    public function testView(string $status): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        self::getContainer()->get('security.token_storage');

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::new()
            ->withoutPersisting()
            ->create([
                'currencyCode' => 'USD',
                'name' => 'Johnston PLC',
                'website' => 'https://www.example.com',
                'vatNumber' => 'GB123456789',
            ])->object();

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => $status,
                'total' => '100.00',
                'balance' => '100.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'items' => [
                    (new Item())
                        ->setDescription('Test Item')
                        ->setPrice('100.00')
                        ->setQty(1),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'tax' => 0,
            ])
            ->object();

        $uuid = Uuid::fromString('181aaf4a-0097-11ef-9b64-5a2cf21a5680');
        $invoice->setId($uuid)
            ->setUuid($uuid)
            ->setInvoiceId('INV-2021-0001')
        ;

        $template = $action($request, $invoice);

        $response = $twig->resolveTemplate($template->getTemplate())->renderBlock('content', $template->getParams());

        $this->assertMatchesHtmlSnapshot($response);
    }

    public function testViewWithPayments(): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        self::getContainer()->get('security.token_storage');

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get('doctrine')->getRepository(Payment::class),
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::new()
            ->withoutPersisting()
            ->create([
                'currencyCode' => 'USD',
                'name' => 'Johnston PLC',
                'website' => 'https://www.example.com',
                'vatNumber' => 'GB123456789',
            ])->object();

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => 'paid',
                'total' => '100.00',
                'balance' => '100.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'items' => [
                    (new Item())
                        ->setDescription('Test Item')
                        ->setPrice('100.00')
                        ->setQty(1),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'due' => new DateTimeImmutable('2021-09-30'),
                'invoiceDate' => new DateTimeImmutable('2021-09-30'),
                'tax' => 0,
            ])
            ->object();

        $payment = new Payment();
        $payment->setTotalAmount(10000);
        $payment->setMethod((new PaymentMethod())->setName('Credit Card'));
        $payment->setStatus('captured');
        $payment->setCurrencyCode('USD');
        $invoice->addPayment($payment);

        $uuid = Uuid::fromString('181aaf4a-0097-11ef-9b64-5a2cf21a5680');
        $invoice->setId($uuid)
            ->setUuid($uuid)
            ->setInvoiceId('INV-2021-0001')
        ;

        $template = $action($request, $invoice);

        $response = $twig->resolveTemplate($template->getTemplate())->renderBlock('content', $template->getParams());

        $this->assertMatchesHtmlSnapshot($response);
    }

    /**
     * @return iterable<array{0: string}>
     */
    public function invoiceStatusProvider(): iterable
    {
        $reflectionClass = new ReflectionClass(Graph::class);

        foreach ($reflectionClass->getConstants() as $constant => $value) {
            if ($value !== Graph::STATUS_NEW && str_starts_with($constant, 'STATUS_')) {
                yield "Status {$value}" => [$value];
            }
        }
    }
}
