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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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

        if ($session instanceof SessionInterface && (!$session->has('installation_step') || true !== filter_var($session->remove('installation_step'), FILTER_VALIDATE_BOOLEAN))) {
            throw new ApplicationInstalledException();
        }

        $binDir = $this->projectDir.'/bin';

        return new Template('@SolidInvoiceInstall/finish.html.twig', ['binDir' => $binDir]);
    }
}
