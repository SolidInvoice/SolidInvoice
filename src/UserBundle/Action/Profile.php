<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
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
