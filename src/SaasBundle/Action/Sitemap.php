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

use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Everything else sits behind a login, so the only canonical public pages are the auth screens.
 */
final readonly class Sitemap
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ToggleInterface $toggle,
    ) {
    }

    public function __invoke(): Response
    {
        $routes = ['_login_main'];

        if ($this->toggle->isActive('allow_registration')) {
            $routes[] = '_register';
        }

        $urls = '';

        foreach ($routes as $route) {
            $urls .= sprintf("  <url><loc>%s</loc></url>\n", htmlspecialchars($this->urlGenerator->generate($route, [], UrlGeneratorInterface::ABSOLUTE_URL), ENT_XML1));
        }

        return new Response(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$urls}</urlset>\n",
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }
}
