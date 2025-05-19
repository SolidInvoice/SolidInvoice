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

namespace SolidInvoice\UserBundle\Email;

use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

final class ResetPasswordEmail extends TemplatedEmail
{
    public function __construct(
        User $user,
        ResetPasswordToken $resetToken,
    ) {
        parent::__construct();
        $this->to($user->getEmail());
        $this->subject('Your password reset request');
        $this->htmlTemplate('@SolidInvoiceUser/Email/reset_password.html.twig');
        $this->textTemplate('@SolidInvoiceUser/Email/reset_password.txt.twig');
        $this->context([
            'user' => $user,
            'resetToken' => $resetToken,
        ]);
    }
}
