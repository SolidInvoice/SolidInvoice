<?php

namespace CSBill\CoreBundle\Menu\Core;

use CSBill\CoreBundle\Menu\Builder\BuilderInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use SYmfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AuthenticatedMenu extends ContainerAware implements BuilderInterface
{
    public function validate()
    {
        try {
            $security = $this->container->get('security.context');
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }

        return $security->isGranted('IS_AUTHENTICATED_FULLY');
    }
}
