<?php

namespace CSBill\ClientBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContactDetailPrimary extends Constraint {

    public $message = 'This contact should have at least 1 primary email address';

    public function validatedBy()
    {
        return 'contact_detail_primary';
    }
}