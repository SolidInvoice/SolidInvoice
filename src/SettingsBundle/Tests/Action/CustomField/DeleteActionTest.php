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

namespace SolidInvoice\SettingsBundle\Tests\Action\CustomField;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\SettingsBundle\Action\CustomField\DeleteAction;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

final class DeleteActionTest extends KernelTestCase
{
    public function testInvalidCsrfTokenRedirectsWithErrorFlashInsteadOfThrowing(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $em = $this->createStub(EntityManagerInterface::class);

        /** @var FeatureGate $featureGate */
        $featureGate = $container->get('test.' . FeatureGate::class);

        $action = new DeleteAction($em, $featureGate);
        $action->setContainer($container);

        $fieldId = new Ulid();
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/settings/custom-fields/' . $fieldId . '/delete', Request::METHOD_POST);
        $request->setSession($session);

        $requestStack = $container->get('request_stack');
        $requestStack->push($request);

        $request->request->set('_token', 'invalid_token');

        $response = $action($request, (string) $fieldId);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertStringEndsWith('/settings/custom-fields', $response->getTargetUrl());

        /** @var FlashBagInterface $flashBag */
        $flashBag = $session->getBag('flashes');
        $flashes = $flashBag->all();
        self::assertArrayHasKey('error', $flashes);
        self::assertContains('Invalid CSRF token. Please try again.', $flashes['error']);
    }

    public function testValidCsrfTokenPassesCsrfCheck(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('find')->willReturn(null);

        /** @var FeatureGate $featureGate */
        $featureGate = $container->get('test.' . FeatureGate::class);

        $action = new DeleteAction($em, $featureGate);
        $action->setContainer($container);

        $fieldId = new Ulid();
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/settings/custom-fields/' . $fieldId . '/delete', Request::METHOD_POST);
        $request->setSession($session);

        $requestStack = $container->get('request_stack');
        $requestStack->push($request);

        // Generate a real CSRF token for the expected key
        $csrfTokenManager = $container->get('security.csrf.token_manager');
        $token = $csrfTokenManager->getToken('cf_delete_' . $fieldId);
        $request->request->set('_token', $token->getValue());

        // A valid CSRF token passes the check; the action proceeds to the entity
        // lookup and throws NotFoundHttpException (em->find returns null).
        $this->expectException(NotFoundHttpException::class);
        $action($request, (string) $fieldId);
    }
}
