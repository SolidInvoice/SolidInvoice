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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Listener\DiscoveryLinkHeaderListener;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(DiscoveryLinkHeaderListener::class)]
#[Group('functional')]
final class DiscoveryLinkHeaderTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testHomepageAdvertisesDiscoveryLinks(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/');

        $links = (array) $client->getResponse()->headers->all('Link');
        $joined = implode("\n", $links);

        self::assertStringContainsString('rel="api-catalog"', $joined);
        self::assertStringContainsString('/.well-known/api-catalog', $joined);
        self::assertStringContainsString('rel="service-desc"', $joined);
        self::assertStringContainsString('rel="service-doc"', $joined);
        self::assertStringContainsString('rel="mcp-server-card"', $joined);
    }

    public function testWellKnownEndpointsDoNotEmitLinks(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/.well-known/api-catalog');

        self::assertEmpty($client->getResponse()->headers->all('Link'));
    }
}
