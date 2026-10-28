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

namespace SolidInvoice\ApiBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\ApiResource\DocumentSettings;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('functional')]
final class DocumentSettingsTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return DocumentSettings::class;
    }

    public function testANewCompanyStartsFromTheDefaults(): void
    {
        $settings = $this->requestGet('/api/settings/documents');

        self::assertSame('net_30', $settings['paymentTerms']);
        self::assertSame(30, $settings['quoteValidityDays']);
        self::assertNull($settings['invoiceNotes']);
        self::assertTrue($settings['remindersEnabled']);
    }

    public function testChangesAreSavedForThisCompanyOnly(): void
    {
        $other = CompanyFactory::createOne();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $this->requestPatch('/api/settings/documents', [
            'invoiceNotes' => "Pay by EFT to:\nAcme Bank 123456",
            'quoteValidityDays' => 14,
            'paymentTerms' => 'net_7',
            'invoiceNumberPrefix' => 'INV-',
        ]);

        $settings = $this->requestGet('/api/settings/documents');
        self::assertSame("Pay by EFT to:\nAcme Bank 123456", $settings['invoiceNotes']);
        self::assertSame(14, $settings['quoteValidityDays']);
        self::assertSame('net_7', $settings['paymentTerms']);
        self::assertSame('INV-', $settings['invoiceNumberPrefix']);
        self::assertNull($settings['quoteNotes']);

        $config = self::getContainer()->get(SystemConfig::class);
        self::assertNull($config->get('invoice/default_notes', $other));
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
        self::assertSame('INV-', $config->get('invoice/id_generation/id_prefix'));
    }

    public function testAnInvalidValueIsRejected(): void
    {
        self::$client->request(Request::METHOD_PATCH, '/api/settings/documents', [
            'json' => ['quoteValidityDays' => 0, 'invoiceBccAddress' => 'not an email'],
            'headers' => ['content-type' => 'application/merge-patch+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['propertyPath' => 'quoteValidityDays'], ['propertyPath' => 'invoiceBccAddress']]]);
    }
}
