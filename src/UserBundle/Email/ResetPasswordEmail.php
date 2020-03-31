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

namespace SolidInvoice\UserBundle\Email;

use SolidInvoice\MailerBundle\Template\HtmlTemplateMessage;
use SolidInvoice\MailerBundle\Template\Template;
use SolidInvoice\MailerBundle\Template\TextTemplateMessage;
use SolidInvoice\UserBundle\Entity\User;

final class ResetPasswordEmail extends \Swift_Message implements HtmlTemplateMessage, TextTemplateMessage
{
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        parent::__construct();

        $this->user = $user;
        $this->setTo($user->getEmail());
    }

    public function getHtmlTemplate(): Template
    {
        return new Template('@SolidInvoiceUser/Email/reset_password.html.twig', ['user' => $this->user]);
    }

    public function getTextTemplate(): Template
    {
        return new Template('@SolidInvoiceUser/Email/reset_password.txt.twig', ['user' => $this->user]);
    }
}
