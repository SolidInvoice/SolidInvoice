<?php

/*
 * This file is part of the CSBillUserBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\UserBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use Symfony\Component\Security\Core\SecurityContext;

class LoginController extends BaseController
{
    /**
     * Page for login
     */
    public function loginAction()
    {
        if ($this->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $this->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $this->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
            $this->get('request')->getSession()->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render('CSBillUserBundle:Login:login.html.twig', array(
            'last_username' => $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        ));
    }

    /**
     * Authenticates the user
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    /**
     * Log user out
     */
    public function logoutAction()
    {
        // The security layer will intercept this request
    }
}
