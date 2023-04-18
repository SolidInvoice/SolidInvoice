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

namespace SolidInvoice\CoreBundle\Response;

use Generator;

interface FlashResponse
{
    public const FLASH_SUCCESS = 'success';

    public const FLASH_INFO = 'info';

    public const FLASH_ERROR = 'error';

    public const FLASH_WARNING = 'warning';

    public const FLASH_DANGER = 'danger';

    /**
     * Return a Generator to set flash messages, with the key as the type and the value as the message.
     *
     * E.G yield self::FLASH_SUCCESS => 'my.flash.message'
     *
     * @return Generator<string, string>
     */
    public function getFlash(): Generator;
}
