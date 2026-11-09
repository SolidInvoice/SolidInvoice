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

namespace SolidInvoice\SaasBundle\Action;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class Robots
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): Response
    {
        $content = sprintf(
            "User-agent: *\nAllow: /\n\nSitemap: %s\nAgentmap: %s\n",
            $this->urlGenerator->generate('_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->urlGenerator->generate('mcp_well_known_ai_catalog', [], UrlGeneratorInterface::ABSOLUTE_URL),
        );

        return new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
