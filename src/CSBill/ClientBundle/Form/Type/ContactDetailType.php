<?php

namespace CSBill\ClientBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class ContactDetailType extends AbstractType
{
    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
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