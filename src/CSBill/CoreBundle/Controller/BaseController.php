<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as Base;

abstract class BaseController extends Base
{
    /**
     * Return a instance of the doctrine entity manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getEm()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Get a doctrine repository
     *
     * @param  string                                        $repository
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository($repository)
    {
        return $this->getEm()->getRepository($repository);
    }

    /**
     * Adds a message to the session flash
     *
     * @param string $message The message to add to the session flash
     * @param string $type    The flash message type (notice, success, error etc)
     *
     * @return $this
     */
    protected function flash($message, $type = 'notice')
    {
        $this->get('session')->getFlashBag()->add($type, $message);

        return $this;
    }

    /**
     * Translates a message
     *
     * @param  string $message
     * @return string
     */
    protected function trans($message)
    {
        return $this->get('translator')->trans($message);
    }
}
