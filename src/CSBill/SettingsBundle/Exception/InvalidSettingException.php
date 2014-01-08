<?php

/*
 * This file is part of the CSBillSettingsBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\SettingsBundle\Exception;

/**
 * Class InvalidSettingException
 * @package CSBill\SettingsBundle\Exception
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
