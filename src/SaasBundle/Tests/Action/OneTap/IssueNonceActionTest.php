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

namespace SolidInvoice\SaasBundle\Tests\Action\OneTap;

use const JSON_THROW_ON_ERROR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Action\OneTap\IssueNonceAction;
use SolidInvoice\SaasBundle\Security\OneTap\NonceStore;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function json_decode;

#[CoversClass(IssueNonceAction::class)]
final class IssueNonceActionTest extends TestCase
{
    public function testReturnsANonceWhenTheFeatureIsEnabled(): void
    {
        $action = new IssueNonceAction($this->toggle(true), new NonceStore(new ArrayAdapter(), 300));

        $response = $action(new Request());

        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('nonce', $payload);
        self::assertNotSame('', $payload['nonce']);
        self::assertSame(300, $payload['ttl']);
    }

    public function testReturnsNotFoundWhenTheFeatureIsDisabled(): void
    {
        $action = new IssueNonceAction($this->toggle(false), new NonceStore(new ArrayAdapter(), 300));

        $this->expectException(NotFoundHttpException::class);

        $action(new Request());
    }

    private function toggle(bool $active): ToggleInterface
    {
        $toggle = $this->createMock(ToggleInterface::class);
        $toggle->method('isActive')->willReturn($active);

        return $toggle;
    }
}
