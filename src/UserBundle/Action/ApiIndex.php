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

namespace SolidInvoice\UserBundle\Action;

use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ApiIndex extends AbstractController
{
    public function __construct(
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(): Response
    {
        if (! $this->featureGate->isEnabled('rest_api_access')) {
            return $this->render('@SolidInvoiceUser/Api/gated.html.twig');
        }

        return $this->render('@SolidInvoiceUser/Api/index.html.twig');
    }
}
