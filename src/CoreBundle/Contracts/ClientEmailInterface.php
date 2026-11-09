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

namespace SolidInvoice\CoreBundle\Contracts;

/**
 * An email that goes to a client rather than to a user of the app. Its From is the system
 * sender, so {@see \SolidInvoice\CoreBundle\Listener\EmailFromListener} gives it a Reply-To
 * that reaches the company instead.
 */
interface ClientEmailInterface
{
}
