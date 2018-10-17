<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle;

final class Context implements \ArrayAccess
{
    private $properties = [];

    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    public static function create(array $properties = [])
    {
        return new self($properties);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->properties);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }
}
