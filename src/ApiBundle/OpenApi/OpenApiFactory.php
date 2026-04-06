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

namespace SolidInvoice\ApiBundle\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\Model\Tag;
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
        $openApi = $this->decorated->__invoke($context);

        $descriptions = [
            'Invoice' => 'Manage invoices and their lifecycle transitions',
            'Quote' => 'Manage quotes and convert them to invoices',
            'Client' => 'Manage clients, their contacts, and credit',
            'Contact' => 'Manage contacts belonging to a client',
            'Payment' => 'Track and record payments against invoices',
            'Tax' => 'Manage tax rates applied to invoice lines',
            'RecurringInvoice' => 'Manage recurring invoice templates and generate invoices from them',
            'ApiToken' => 'Manage API tokens for authentication',
        ];

        $tags = array_map(
            static fn (Tag $tag) => new Tag($tag->getName(), $descriptions[$tag->getName()] ?? $tag->getDescription()),
            $openApi->getTags(),
        );

        return $openApi
            ->withServers([
                new Server($this->urlGenerator->generate('_home', [], UrlGeneratorInterface::ABSOLUTE_URL)),
            ])
            ->withTags($tags);
    }
}
