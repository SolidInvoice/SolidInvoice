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

namespace SolidInvoice\SaasBundle\Email;

use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see \SolidInvoice\SaasBundle\Tests\Email\TrialDowngradedEmailTest
 */
final class TrialDowngradedEmail extends TemplatedEmail
{
    public function __construct(User $user, Company $company, TranslatorInterface $translator)
    {
        parent::__construct();

        $this->to(Address::create((string) $user->getEmail()));
        $this->subject($translator->trans('trial_downgraded.subject', [], 'email'));
        $this->htmlTemplate('@SolidInvoiceSaas/Email/trial_downgraded.html.twig');
        $this->textTemplate('@SolidInvoiceSaas/Email/trial_downgraded.txt.twig');
        $this->context([
            'user' => $user,
            'company' => $company,
        ]);
    }
}
