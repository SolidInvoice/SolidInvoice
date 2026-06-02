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

namespace SolidInvoice\ApiBundle\Tests\Event\Listener;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Event\Listener\FormatNegotiationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class FormatNegotiationListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private FormatNegotiationListener $listener;

    protected function setUp(): void
    {
        $this->listener = new FormatNegotiationListener();
    }

    private function makeEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(M::mock(KernelInterface::class), $request, $type);
    }

    public function testSkipsSubRequests(): void
    {
        $request = Request::create('/api/docs.json');
        $request->setRequestFormat('json');
        $request->headers->set('Accept', 'text/html');

        $this->listener->__invoke($this->makeEvent($request, HttpKernelInterface::SUB_REQUEST));

        // Accept header must not be changed for sub-requests
        self::assertSame('text/html', $request->headers->get('Accept'));
    }

    public function testSkipsNonApiRoutes(): void
    {
        $request = Request::create('/some/other/path.json');
        $request->setRequestFormat('json');
        $request->headers->set('Accept', 'text/html');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('text/html', $request->headers->get('Accept'));
    }

    public function testSkipsWhenNoFormatSet(): void
    {
        $request = Request::create('/api/docs');
        $request->headers->set('Accept', 'text/html');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('text/html', $request->headers->get('Accept'));
    }

    public function testSkipsWhenFormatNotInMap(): void
    {
        $request = Request::create('/api/docs.csv');
        $request->setRequestFormat('csv');
        $request->headers->set('Accept', 'text/html');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('text/html', $request->headers->get('Accept'));
    }

    public function testSkipsWhenAcceptIsEmpty(): void
    {
        $request = Request::create('/api/docs.json');
        $request->setRequestFormat('json');
        $request->headers->remove('Accept');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertNull($request->headers->get('Accept'));
    }

    public function testSkipsWhenAcceptAlreadyIncludesExpectedMime(): void
    {
        $request = Request::create('/api/docs.json');
        $request->setRequestFormat('json');
        $request->headers->set('Accept', 'application/json, text/html');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('application/json, text/html', $request->headers->get('Accept'));
    }

    public function testSkipsWhenAcceptIncludesWildcard(): void
    {
        $request = Request::create('/api/docs.json');
        $request->setRequestFormat('json');
        $request->headers->set('Accept', 'text/html, */*');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('text/html, */*', $request->headers->get('Accept'));
    }

    public function testOverridesAcceptForJsonFormatWithBotAcceptHeader(): void
    {
        $request = Request::create('/api/docs.json');
        $request->setRequestFormat('json');
        $request->headers->set(
            'Accept',
            'text/html, application/rss+xml, application/atom+xml, text/xml, text/rss+xml, application/xhtml+xml'
        );

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('application/json', $request->headers->get('Accept'));
    }

    public function testOverridesAcceptForJsonldFormat(): void
    {
        $request = Request::create('/api/docs.jsonld');
        $request->setRequestFormat('jsonld');
        $request->headers->set('Accept', 'text/html');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('application/ld+json', $request->headers->get('Accept'));
    }

    public function testOverridesAcceptForXmlFormat(): void
    {
        $request = Request::create('/api/docs.xml');
        $request->setRequestFormat('xml');
        $request->headers->set('Accept', 'text/html');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('application/xml', $request->headers->get('Accept'));
    }

    public function testOverridesAcceptForJsonOpenApiFormat(): void
    {
        $request = Request::create('/api/docs.jsonopenapi');
        $request->setRequestFormat('jsonopenapi');
        $request->headers->set('Accept', 'text/html, application/rss+xml');

        $this->listener->__invoke($this->makeEvent($request));

        self::assertSame('application/vnd.openapi+json', $request->headers->get('Accept'));
    }
}
