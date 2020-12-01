<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Action;

use SolidInvoice\AppRequirements;
use SolidInvoice\CoreBundle\Templating\Template;

final class SystemRequirements
{
    /**
     * @var string|null
     */
    private $installed;

    public function __construct(?string $installed)
    {
        $this->installed = $installed;
    }

    public function __invoke()
    {
        return new Template(
            '@SolidInvoiceInstall/system_check.html.twig',
            [
                'requirements' => new AppRequirements(),
            ]
        );
    }
}
