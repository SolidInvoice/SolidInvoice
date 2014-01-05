<?php

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
            'contact_types' => $this->contactTypes
        );
    }

    public function getName()
    {
        return 'client_contact_types_extension';
    }
}
