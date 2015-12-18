<?php
/**
 * This file is part of the CSBill project.
 *
 * @author    pierre
 */

namespace CSBill\CoreBundle\Traits\Entity;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Money\Money;

trait JsonSerialize
{
    private $__serializeExclude = [
        'deletedAt',
        '__serializeExclude',
    ];

    public function jsonSerialize()
    {
        $json = [];

        $ref = ClassUtils::newReflectionObject($this);

        $properties = $ref->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $property) {
            $name = $property->getName();

            if (in_array($name, $this->__serializeExclude)) {
                continue;
            }

            $property->setAccessible(true);
            $value = $property->getValue($this);
            $property->setAccessible(false);

            switch (true) {
                case $value instanceof Collection:
                    $json[$name] = $value->toArray();
                    break;

                case $value instanceof \DateTime:
                    $json[$name] = $value->format(\DateTime::ATOM);
                    break;

                case $value instanceof Money:
                    $json[$name] = MoneyFormatter::toFloat($value);
                    break;

                default:
                    $json[$name] = $value;
            }
        }

        return $json;
    }
}