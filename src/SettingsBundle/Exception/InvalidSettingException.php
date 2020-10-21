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

namespace SolidInvoice\SettingsBundle\Exception;

use RuntimeException;

/**
 * Class InvalidSettingException.
 */
class InvalidSettingException extends RuntimeException
{
    /**
     * @param string $value The name of the invalid setting
     */
    public function __construct(string $value)
    {
        $message = sprintf('Invalid settings key: %s', $value);

        parent::__construct($message);
    }
}
