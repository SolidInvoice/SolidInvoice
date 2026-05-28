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

namespace SolidInvoice\CoreBundle\Tests\Action\BillingTemplate;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use SolidInvoice\CoreBundle\Action\BillingTemplate\Preview;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Sample\BillingSampleFactory;
use SolidInvoice\CoreBundle\Templating\BillingTemplateResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SourcePolicyInterface;
use Twig\Source;
use function json_decode;
use function str_starts_with;

final class PreviewTest extends KernelTestCase
{
    use MockeryPHPUnitIntegration;

    public function testRendersHtmlPreview(): void
    {
        $action = $this->makeAction();
        $request = $this->makeRequest([
            '_token' => 'valid',
            'type' => 'invoice',
            'variant' => 'html',
            'content' => 'Hello {{ invoice.invoiceId }}',
        ]);

        $response = $action($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('html', $payload);
        self::assertStringContainsString('INV-PREVIEW-0001', (string) $payload['html']);
    }

    public function testReportsTwigSyntaxErrorAs422(): void
    {
        $action = $this->makeAction();
        $request = $this->makeRequest([
            '_token' => 'valid',
            'type' => 'invoice',
            'variant' => 'html',
            'content' => '{% if foo %}',
        ]);

        $response = $action($request);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('error', $payload);
    }

    public function testRejectsInvalidCsrfToken(): void
    {
        $action = $this->makeAction(validToken: false);
        $request = $this->makeRequest([
            '_token' => 'invalid',
            'type' => 'invoice',
            'variant' => 'html',
            'content' => 'x',
        ]);

        $response = $action($request);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testRejectsUnknownType(): void
    {
        $action = $this->makeAction();
        $request = $this->makeRequest([
            '_token' => 'valid',
            'type' => 'unknown',
            'variant' => 'html',
            'content' => 'x',
        ]);

        $response = $action($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    private function makeAction(bool $validToken = true): Preview
    {
        $repository = M::mock(BillingTemplateRepository::class);
        $resolver = new BillingTemplateResolver($repository);

        $environment = new Environment(new ArrayLoader());
        $environment->addExtension(new SandboxExtension(
            BillingTemplateResolver::createSecurityPolicy(),
            false,
            new class() implements SourcePolicyInterface {
                public function enableSandbox(Source $source): bool
                {
                    return str_starts_with($source->getName(), BillingTemplateResolver::SANDBOX_NAME_PREFIX);
                }
            },
        ));

        $tokenManager = M::mock(CsrfTokenManagerInterface::class);
        $tokenManager->shouldReceive('isTokenValid')
            ->andReturnUsing(static fn (CsrfToken $token): bool => $validToken && 'valid' === $token->getValue());

        $action = new Preview(
            $resolver,
            new BillingSampleFactory(),
            $environment,
        );

        $container = M::mock(ServiceProviderInterface::class);
        $container->shouldReceive('has')->with('security.csrf.token_manager')->andReturnTrue();
        $container->shouldReceive('get')->with('security.csrf.token_manager')->andReturn($tokenManager);

        $action->setContainer($container);

        return $action;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function makeRequest(array $payload): Request
    {
        return Request::create('/settings/billing-templates/preview', 'POST', [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
