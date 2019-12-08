<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
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
