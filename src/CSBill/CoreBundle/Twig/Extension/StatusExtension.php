<?php

/*
 * This file is part of the CSBill package.
*
* (c) Pierre du Plessis <info@customscripts.co.za>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CSBill\CoreBundle\Twig\Extension;

/**
 * This class is a twig extension that gives some shortcut methods to client statuses
 *
 * @author Pierre du Plessis
 */
class StatusExtension extends \Twig_Extension
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @param \Twig_Environment $environment
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns an array of all the helper functions for the client status
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
                    new \Twig_SimpleFunction('status_label', array($this, 'getStatusLabel'), array('is_safe' => array('html')))
                );
    }

    /**
     * Return the status converted into a label string
     *
     * @param mixed $object
     * @return string
     */
    public function getStatusLabel($object)
    {
        return $this->environment->render('CSBillCoreBundle:Status:label.html.twig', array('entity' => $object));
    }

    /**
     * Get the name of the twig extension
     *
     * @return string
     */
    public function getName()
    {
        return 'csbill_core.status';
    }
}
