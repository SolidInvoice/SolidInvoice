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

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class Profile
{
    public function __invoke(TokenStorageInterface $storage)
    {
        return new Template('@SolidInvoiceUser/Profile/show.html.twig');
    }
}
