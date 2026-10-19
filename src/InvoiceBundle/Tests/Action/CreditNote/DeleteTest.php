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

namespace SolidInvoice\InvoiceBundle\Tests\Action\CreditNote;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Delete;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Draft-only enforcement, per `design` §7 on SOL-69 — the same rule {@see EditTest} covers.
 */
#[CoversClass(Delete::class)]
final class DeleteTest extends TestCase
{
    public function testDraftCreditNoteIsRemoved(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setStatus(CreditNoteStatus::Draft);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())->method('remove')->with($creditNote);
        $em->expects($this->once())->method('flush');
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes');

        $action = new Delete($doctrine, $csrfTokenManager, $router);

        $request = Request::create('/', Request::METHOD_DELETE, ['_token' => 'valid']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $action($creditNote, $request, $request->getSession());

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testIssuedCreditNoteIsNotRemoved(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setStatus(CreditNoteStatus::Issued);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->never())->method('remove');
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes/123');

        $action = new Delete($doctrine, $csrfTokenManager, $router);

        $request = Request::create('/', Request::METHOD_DELETE, ['_token' => 'valid']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $action($creditNote, $request, $request->getSession());

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testInvalidCsrfTokenIsRejected(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setStatus(CreditNoteStatus::Draft);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->never())->method('remove');
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes/123');

        $action = new Delete($doctrine, $csrfTokenManager, $router);

        $request = Request::create('/', Request::METHOD_DELETE, ['_token' => 'invalid']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $action($creditNote, $request, $request->getSession());

        self::assertInstanceOf(RedirectResponse::class, $response);
    }
}
