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

namespace SolidInvoice\CoreBundle\Action\BillingTemplate;

use InvalidArgumentException;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Sample\BillingSampleFactory;
use SolidInvoice\CoreBundle\Templating\BillingTemplateResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use function in_array;
use function strtolower;

/**
 * Renders an ad-hoc preview of an in-memory billing template.
 *
 * Accepts JSON: {"type": "invoice|quote", "variant": "html|pdf|email", "content": "..."}
 *
 * Returns 200 with the rendered HTML or 422 with the Twig error message.
 */
final class Preview extends AbstractController
{
    public function __construct(
        private readonly BillingTemplateResolver $resolver,
        private readonly BillingSampleFactory $sampleFactory,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = json_decode((string) $request->getContent(), true);

        if (! is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $token = (string) ($payload['_token'] ?? '');

        if (! $this->isCsrfTokenValid('billing_template_preview', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $type = strtolower((string) ($payload['type'] ?? ''));
        $variant = strtolower((string) ($payload['variant'] ?? ''));
        $content = (string) ($payload['content'] ?? '');

        if (! in_array($type, [BillingTemplate::TYPE_INVOICE, BillingTemplate::TYPE_QUOTE], true)) {
            return new JsonResponse(['error' => 'Unsupported type'], Response::HTTP_BAD_REQUEST);
        }

        if (! in_array($variant, [BillingTemplate::VARIANT_HTML, BillingTemplate::VARIANT_PDF, BillingTemplate::VARIANT_EMAIL], true)) {
            return new JsonResponse(['error' => 'Unsupported variant'], Response::HTTP_BAD_REQUEST);
        }

        $context = $this->buildContext($type);

        try {
            $html = $this->resolver->renderPreview($this->twig, $type, $variant, $content, $context);
        } catch (TwigError $error) {
            return new JsonResponse(['error' => $error->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (InvalidArgumentException $error) {
            return new JsonResponse(['error' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(string $type): array
    {
        return match ($type) {
            BillingTemplate::TYPE_INVOICE => ['invoice' => $this->sampleFactory->createInvoice()],
            BillingTemplate::TYPE_QUOTE => ['quote' => $this->sampleFactory->createQuote()],
            default => [],
        };
    }
}
