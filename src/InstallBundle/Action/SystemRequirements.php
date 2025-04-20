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

namespace SolidInvoice\InstallBundle\Action;

use SolidInvoice\AppRequirements;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

final class SystemRequirements extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'SOLIDINVOICE_RUNTIME')]
        private readonly ?string $runtime = null
    ) {
    }

    public function __invoke(): Response
    {
        if ('frankenphp' === $this->runtime) {
            /*
             * When running with Frankenphp, the correct php version,
             * all the required extensions and php.ini config is already set,
             * so no need to check the system requirements (which should always be valid).
             * It will also confuse the user since they can't change anything
             * that this page would display.
             */
            return $this->redirectToRoute('_install_config');
        }

        return $this->render(
            '@SolidInvoiceInstall/system_check.html.twig',
            [
                'requirements' => new AppRequirements(),
            ]
        );
    }
}
