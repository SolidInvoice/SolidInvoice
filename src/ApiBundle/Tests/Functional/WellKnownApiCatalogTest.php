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

namespace SolidInvoice\ApiBundle\Tests\Functional;

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\ApiBundle\Action\WellKnownApiCatalog
 *
 * @group functional
 */
final class WellKnownApiCatalogTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testApiCatalog(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request('GET', '/.well-known/api-catalog');

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('application/linkset+json', (string) $client->getResponse()->headers->get('Content-Type'));

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertIsArray($data);
        self::assertArrayHasKey('linkset', $data);
        self::assertCount(1, $data['linkset']);

        $entry = $data['linkset'][0];
        self::assertArrayHasKey('anchor', $entry);
        self::assertStringEndsWith('/api', $entry['anchor']);
        self::assertArrayHasKey('service-desc', $entry);
        self::assertArrayHasKey('service-doc', $entry);
        self::assertSame('text/html', $entry['service-doc'][0]['type']);
    }
}
