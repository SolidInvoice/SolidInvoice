<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Exception;

use RuntimeException;

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
