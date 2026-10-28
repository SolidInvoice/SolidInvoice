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

namespace SolidInvoice\ApiBundle\Action\Company;

use SolidInvoice\CoreBundle\Company\CompanyLogo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use function hash;

/**
 * The company logo image, for `logoUrl`.
 */
#[AsController]
#[Route(path: '/company/logo', name: 'api_company_logo_show', methods: ['GET'])]
final readonly class ShowCompanyLogo
{
    public function __construct(
        private CompanyLogo $logo,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $logo = $this->logo->get() ?? throw new NotFoundHttpException();

        $response = new Response($logo['content'], headers: ['Content-Type' => $logo['mimeType']]);
        $response->setPrivate();
        $response->setEtag(hash('xxh128', $logo['content']));
        $response->isNotModified($request);

        return $response;
    }
}
