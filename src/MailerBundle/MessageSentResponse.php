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

final class MessageSentResponse
{
    /**
     * @var array
     */
    private $failed;

    /**
     * @var int
     */
    private $totalFailed;

    public function __construct(array $failed = [])
    {
        $this->failed = $failed;
        $this->totalFailed = \count($failed);
    }

    public function isSuccess(): bool
    {
        return 0 === $this->totalFailed;
    }
}
