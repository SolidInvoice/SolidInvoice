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

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InstallBundle\Exception\ApplicationInstalledException;
use Symfony\Component\HttpFoundation\Request;

final class Finish
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function __invoke(Request $request)
    {
        $session = $request->getSession();
        if (!$session->has('installation_step') || true !== $session->get('installation_step')) {
            throw new ApplicationInstalledException();
        }

        $binDir = $this->projectDir.'/bin';

        return new Template('@SolidInvoiceInstall/finish.html.twig', ['binDir' => $binDir]);
    }
}
