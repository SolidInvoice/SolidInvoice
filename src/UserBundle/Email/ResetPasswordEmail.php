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
    private UserInterface $user;

    public function __construct(UserInterface $user)
    {
        parent::__construct();

        $this->user = $user;
        $this->to($user->getEmail());
        $this->subject('Password Reset Request');
    }

    public function getHtmlTemplate(): string
    {
        return '@SolidInvoiceUser/Email/reset_password.html.twig';
    }

    public function getTextTemplate(): string
    {
        return '@SolidInvoiceUser/Email/reset_password.txt.twig';
    }

    public function getContext(): array
    {
        return \array_merge(['user' => $this->user], parent::getContext());
    }
}
