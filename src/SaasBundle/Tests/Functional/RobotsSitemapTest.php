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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Action\Robots;
use SolidInvoice\SaasBundle\Action\Sitemap;
use SolidInvoice\Test\SaasKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(Robots::class)]
#[CoversClass(Sitemap::class)]
#[Group('functional')]
final class RobotsSitemapTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    public function testRobotsReferencesSitemapAndCatalog(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/robots.txt');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');

        $content = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('Sitemap: http://localhost/sitemap.xml', $content);
        self::assertStringContainsString('Agentmap: http://localhost/.well-known/ai-catalog.json', $content);
    }

    public function testSitemapListsLoginPage(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string((string) $client->getResponse()->getContent());

        self::assertNotFalse($xml);
        self::assertSame('urlset', $xml->getName());
        self::assertContains('http://localhost/login', array_map(strval(...), iterator_to_array($xml->url->loc, false)));
    }
}
