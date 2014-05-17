<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'status_label',
                array($this, 'renderStatusLabel'),
                array('is_safe' => array('html')
                )
            )
        );
    }

    /**
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'status',
                array($this, 'getStatusLabel'),
                array('is_safe' => array('html')
                )
            )
        );
    }

    /**
     * @param  mixed  $object
     * @return string
     */
    public function getStatusLabel($object)
    {
        return $this->renderStatusLabel($object->getStatus());
    }

    /**
     * Return the status converted into a label string
     *
     * @param  mixed  $object
     * @return string
     */
    public function renderStatusLabel($object)
    {
        if (is_array($object) && array_key_exists('status_label', $object) && array_key_exists('status', $object)) {
            $object = array(
                'name' => $object['status'],
                'label' => $object['status_label'],
            );
        }

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
