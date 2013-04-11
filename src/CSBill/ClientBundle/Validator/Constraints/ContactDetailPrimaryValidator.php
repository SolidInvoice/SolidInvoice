<?php

namespace CSBill\ClientBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ContactDetailPrimaryValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $types = $this->getRequiredTypes();

        foreach ($value as $val) {
            $type = (string) $val->getType();

            if (in_array($type, $types)) {
                if ((bool) $val->isPrimary()) {
                    unset($types[array_search($type, $types)]);
                }
            }
        }

        unset($type);

        if (!empty($types)) {
            foreach ($types as $type) {
                $this->context->addViolation('This contact should have at least one primary ' . $type, array());
            }
        }
    }

    protected function getRequiredTypes()
    {
        return array('email');
    }
}
