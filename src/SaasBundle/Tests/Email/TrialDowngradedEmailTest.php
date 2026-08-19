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

namespace SolidInvoice\SaasBundle\Tests\Email;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SaasBundle\Email\TrialDowngradedEmail;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TrialDowngradedEmailTest extends TestCase
{
    public function testEmailIsAddressedToTheUserWithTheExpectedTemplates(): void
    {
        $user = new User()->setEmail('owner@example.com');
        $company = new Company()->setName('Acme');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('You are all set on SolidInvoice');

        $email = new TrialDowngradedEmail($user, $company, $translator);

        self::assertSame('@SolidInvoiceSaas/Email/trial_downgraded.html.twig', $email->getHtmlTemplate());
        self::assertSame('@SolidInvoiceSaas/Email/trial_downgraded.txt.twig', $email->getTextTemplate());
        self::assertSame('You are all set on SolidInvoice', $email->getSubject());
        self::assertSame('owner@example.com', $email->getTo()[0]->getAddress());
    }
}
