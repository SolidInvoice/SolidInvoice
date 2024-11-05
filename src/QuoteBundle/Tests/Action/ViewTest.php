<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Tests\Action;

use DateTimeImmutable;
use Psr\Log\NullLogger;
use ReflectionClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\QuoteBundle\Action\View;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

final class ViewTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use MatchesSnapshots;
    use Factories;

    /**
     * @dataProvider quoteStatusProvider
     */
    public function testView(string $status): void
    {
        $request = Request::createFromGlobals();
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $twig = self::getContainer()->get(Environment::class);

        $action = new View(
            new Generator('', new NullLogger()),
            $twig
        );

        $client = ClientFactory::createOne([
            'currencyCode' => 'USD',
            'name' => 'Johnston PLC',
            'website' => 'https://www.example.com',
            'vatNumber' => 'GB123456789',
        ])->_real();

        /** @var Quote $quote */
        $quote = QuoteFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => $status,
                'total' => '100.00',
                'baseTotal' => '100.00',
                'created' => new DateTimeImmutable('2021-09-01'),
                'lines' => [
                    (new Line())
                        ->setDescription('Test Line')
                        ->setPrice('100.00')
                        ->setQty(1),
                ],
                'terms' => 'Test Terms',
                'notes' => 'Test Notes',
                'discount' => new Discount(),
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString('181aaf4a-0097-11ef-9b64-5a2cf21a5680');
        $quote->setId($uuid)
            ->setUuid(Uuid::fromString('181aaf4a-0097-11ef-9b64-5a2cf21a5680'))
            ->setQuoteId('QUOT-2021-0001')
        ;

        $template = $action($request, $quote);

        $response = $twig->resolveTemplate($template->getTemplate())->renderBlock('content', $template->getParams());

        $this->assertMatchesHtmlSnapshot($response);
    }

    /**
     * @return iterable<array{0: string}>
     */
    public function quoteStatusProvider(): iterable
    {
        $reflectionClass = new ReflectionClass(Graph::class);

        foreach ($reflectionClass->getConstants() as $constant => $value) {
            if ($value !== Graph::STATUS_NEW && str_starts_with($constant, 'STATUS_')) {
                yield "Status {$value}" => [$value];
            }
        }
    }
}
