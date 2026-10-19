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
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Edit;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Draft-only enforcement, per `design` §7 on SOL-69.
 */
#[CoversClass(Edit::class)]
final class EditTest extends TestCase
{
    public function testIssuedCreditNoteRedirectsAwayFromTheEditForm(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setStatus(CreditNoteStatus::Issued);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->never())->method('create');

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes/123');

        $action = new Edit(
            $formFactory,
            $router,
            $this->createStub(WorkflowInterface::class),
            $this->createStub(ManagerRegistry::class),
            $this->createStub(TotalCalculator::class),
        );

        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $action($request, $creditNote);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDraftCreditNoteCanBeEditedAndIssued(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setStatus(CreditNoteStatus::Draft);

        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('handleRequest');

        $formFactory = $this->createStub(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->willReturn(true);
        $workflow->expects($this->once())->method('apply');

        $em = $this->createStub(ObjectManager::class);
        $em->method('flush');
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes/123');

        $action = new Edit(
            $formFactory,
            $router,
            $workflow,
            $doctrine,
            $this->createStub(TotalCalculator::class),
        );

        $request = Request::create('/', Request::METHOD_POST, ['save' => 'issue']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $action($request, $creditNote);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }
}
