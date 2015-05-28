<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Exception;

/**
 * Class InvalidSettingException.
 */
class InvalidSettingException extends \RuntimeException
{
    /**
     * @param string $value The name of the invalid setting
     */
    public function __construct($value)
    {
        $message = sprintf('Invalid settings option: %s', $value);

        parent::__construct($message);
    }
}
