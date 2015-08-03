<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu\Core;

use CSBill\CoreBundle\Menu\Builder\BuilderInterface;
use SYmfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\DependencyInjection\ContainerAware;

class AuthenticatedMenu extends ContainerAware implements BuilderInterface
{
    /**
     * @return bool
     */
    public function validate()
    {
        try {
            $security = $this->container->get('security.authorization_checker');

            return $security->isGranted('IS_AUTHENTICATED_REMEMBERED');
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }
}
