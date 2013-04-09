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

            // skip contact details that are loaded form the database (some very werid bug, not sure where it comes from)
            if ($val->getId()) {
                continue;
            }

            $type = (string) $val->getType();

            if (isset($types[$type])) {
                $types[$type][] = (bool) $val->isPrimary();
            }
        }

        unset($type);

        foreach (array_keys($types) as $type) {
            $values = array_filter($types[$type], function($item){
                return $item === true;
            });

            if (count($values) > 0) {
                $this->context->addViolation('There should be at least one primary ' . $type, array());
            }
        }

        $error = false;

        if ($error) {
            $this->context->addViolationAt('[0].type', $constraint->message, array());
            \Debug::dump($this->context->getClassName());
            \Debug::dump($this->context->getPropertyName());
            \Debug::dump($this->context->getPropertyPath());
            \Debug::dump($this->context);
        }
    }

    protected function getRequiredTypes()
    {
        return array('email' => array());
    }
}
