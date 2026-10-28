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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * The default terms and notes from Settings fill in a new invoice or quote.
 */
#[Group('functional')]
final class DocumentDefaultsPrefillTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testANewInvoiceStartsWithTheDefaultTermsAndNotes(): void
    {
        $client = $this->signedInClient([
            'invoice/default_terms' => 'Payment within 7 days',
            'invoice/default_notes' => 'Pay by EFT to Acme Bank 123456',
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/invoices/create');

        self::assertResponseIsSuccessful();
        self::assertSame('Payment within 7 days', $crawler->filter('[name$="[terms]"]')->attr('value'));
        self::assertSame('Pay by EFT to Acme Bank 123456', $crawler->filter('[name$="[notes]"]')->attr('value'));
    }

    public function testANewQuoteStartsWithTheDefaultTermsAndNotes(): void
    {
        $client = $this->signedInClient([
            'quote/default_terms' => 'Valid while stock lasts',
            'quote/default_notes' => 'Thank you for the opportunity',
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/quotes/create');

        self::assertResponseIsSuccessful();
        self::assertSame('Valid while stock lasts', $crawler->filter('[name$="[terms]"]')->attr('value'));
        self::assertSame('Thank you for the opportunity', $crawler->filter('[name$="[notes]"]')->attr('value'));
    }

    /**
     * @param array<string, string> $settings
     */
    private function signedInClient(array $settings): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();
        $client->loginUser(UserFactory::createOne(['companies' => [$this->company]]));
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        foreach ($settings as $key => $value) {
            self::getContainer()->get(SystemConfig::class)->set($key, $value);
        }

        return $client;
    }
}
