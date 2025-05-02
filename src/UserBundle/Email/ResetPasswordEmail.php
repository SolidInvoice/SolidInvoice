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

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Core\User\UserInterface;

final class ResetPasswordEmail extends TemplatedEmail
{
    public function __construct(
        private readonly UserInterface $user
    ) {
        parent::__construct();
        $this->to($user->getEmail());
        $this->subject('Password Reset Request');
        $this->htmlTemplate('@SolidInvoiceUser/Email/reset_password.html.twig');
        $this->textTemplate('@SolidInvoiceUser/Email/reset_password.txt.twig');
        $this->context(['user' => $this->user]);
    }
}
