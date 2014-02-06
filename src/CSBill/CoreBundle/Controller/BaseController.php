<?php

/*
 * This file is part of the CSBillCoreBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as Base;

class BaseController extends Base
{
    /**
     * Return a instance of the doctrine entity manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getEm()
    {
        return $this->get('doctrine')->getManager();
    }

    /**
     * Get a doctrine repository
     *
     * @param  string                                        $repository
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($repository)
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
    public function flash($message, $type = 'notice')
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
    public function trans($message)
    {
        return $this->get('translator')->trans($message);
    }
}
