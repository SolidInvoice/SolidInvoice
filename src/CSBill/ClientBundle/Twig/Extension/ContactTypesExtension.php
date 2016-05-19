<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Twig\Extension;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

class ContactTypesExtension extends \Twig_Extension
{
    /**
     * @var array
     */
    protected $contactTypes;

    /**
     * @var ObjectManager
     */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('contactTypes', [$this, 'getContactTypes']),
        ];
    }

    /**
     * @return array
     */
    public function getContactTypes()
    {
        if (null !== $this->contactTypes) {
            return $this->contactTypes;
        }

        /** @var ObjectRepository $repository */
        $repository = $this->doctrine->getRepository('CSBillClientBundle:ContactType');

        $this->contactTypes = $repository->findAll();

        return $this->contactTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'client_contact_types_extension';
    }
}
