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

namespace SolidInvoice\ApiBundle\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\Request;

/**
 * Carries the requested API version into the serializer context, both ways, so the money
 * normalizers can pick the wire format.
 */
#[AsDecorator('api_platform.serializer.context_builder')]
final readonly class ApiVersionContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        #[AutowireDecorated]
        private SerializerContextBuilderInterface $decorated,
    ) {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        // tryFrom, not fromRequest: this also runs while rendering the error for an unknown version.
        if (ApiVersion::tryFrom((string) $request->headers->get(ApiVersion::HEADER)) === ApiVersion::V2026_10) {
            $context[ApiVersion::DECIMAL_MONEY] = true;
        }

        return $context;
    }
}
