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

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Action\View;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

final class ViewTest extends TestCase
{
    use EnsureApplicationInstalled;
    use MatchesSnapshots;

    /**
     * @dataProvider invoiceStatusProvider
     */
    public function testView(string $status): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push($request);

        self::getContainer()->get(TokenStorageInterface::class);

        $twig = self::getContainer()->get('twig');

        $action = new View(
            self::getContainer()->get(PaymentRepository::class),
            self::getContainer()->get(Generator::class),
            $twig
        );

        $client = ClientFactory::createOne([
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
                'created' => new \DateTimeImmutable('2021-09-01'),
                'items' => [
                    (new Item())
                        ->setDescription('Test Item')
                        ->setPrice('100.00')
                        ->setQty(1),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
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

    public function invoiceStatusProvider(): iterable
    {
        $reflectionClass = new \ReflectionClass(Graph::class);

        foreach ($reflectionClass->getConstants() as $constant => $value) {
            if ($value !== Graph::STATUS_NEW && str_starts_with($constant, 'STATUS_')) {
                yield "Status {$value}" => [$value];
            }
        }
    }
}
