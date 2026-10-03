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

namespace SolidInvoice\QuoteBundle\Tests\Action;

use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\QuoteBundle\Action\Edit;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(Edit::class)]
final class EditTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * The money fields scale by the form's `currency` option. A frozen quote must scale by
     * its own currency, not by the client's current one: USD has 2 subunits, JPY has 0, so a
     * stored 100000 is 1000.00 on the document and must be 1000.00 in the form as well.
     */
    public function testEditFormScalesMoneyByTheQuoteCurrencyNotTheClientCurrency(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'JPY', 'company' => $this->company]);
        $quoteId = QuoteFactory::createOne([
            'status' => QuoteStatus::Pending,
            'client' => $client,
            'company' => $this->company,
            'currencyCode' => 'USD',
            'total' => 100000,
        ])->getId();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $quote = $container->get(QuoteRepository::class)->find($quoteId);
        self::assertInstanceOf(Quote::class, $quote);
        self::assertSame('JPY', $quote->getClient()?->getCurrency()->getCode());

        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get('request_stack')->push($request);

        $action = $container->get(Edit::class);
        self::assertInstanceOf(Edit::class, $action);

        $params = $action($request, $quote);

        self::assertIsArray($params);

        $scale = $container->get(CurrencyScale::class);
        self::assertInstanceOf(CurrencyScale::class, $scale);
        $storedTotal = $params['dto']->total;
        self::assertIsString($storedTotal);

        // USD scales by 100; the client's JPY would not scale at all.
        self::assertEqualsWithDelta(
            $scale->toMajorUnit($storedTotal, new Currency('USD')),
            $params['form']['total']->vars['value'],
            0.0001,
        );
    }
}
