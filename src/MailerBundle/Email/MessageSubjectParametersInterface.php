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

namespace SolidInvoice\MailerBundle\Email;

/**
 * An email whose subject is a translation key that needs parameters.
 *
 * @see \SolidInvoice\MailerBundle\Listener\TranslateEmailSubjectListener
 */
interface MessageSubjectParametersInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getMessageSubjectParameters(): array;
}
