<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class ContactDetailType extends AbstractType
{
    /**
     * @return string
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'contact_details';
    }
}
