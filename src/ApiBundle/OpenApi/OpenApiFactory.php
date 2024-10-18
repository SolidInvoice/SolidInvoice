<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsDecorator(
    decorates: 'api_platform.openapi.factory',
    // The default priority is 0, higher priorities are executed first.
    // To avoid having the Lexik JWT Authentication Bundle decorator executed
    // before this one, we set a lower priority.
    priority: -1
)]
final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        // to define base path URL
        return $this->decorated->__invoke($context)
            ->withServers([
                new Model\Server($this->urlGenerator->generate('_home', [], UrlGeneratorInterface::ABSOLUTE_URL)),
            ]);
    }
}
