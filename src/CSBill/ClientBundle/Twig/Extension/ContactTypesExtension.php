<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Twig\Extension;

class ContactTypesExtension extends \Twig_Extension
{
    protected $contactTypes;

    public function __construct(array $contactTypes)
    {
        $this->contactTypes = $contactTypes;
    }

    public function getGlobals()
    {
        return array(
            'contact_types' => $this->contactTypes,
        );
    }

    public function getName()
    {
        return 'client_contact_types_extension';
    }
}
