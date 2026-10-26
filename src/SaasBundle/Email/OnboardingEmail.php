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

use SolidInvoice\MailerBundle\Email\MessageSubjectParametersInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class OnboardingEmail extends TemplatedEmail implements MessageSubjectParametersInterface
{
    /**
     * @param array<string, mixed> $subjectParameters
     */
    public function __construct(
        private readonly array $subjectParameters = [],
    ) {
        parent::__construct();
    }

    public function getMessageSubjectParameters(): array
    {
        return $this->subjectParameters;
    }
}
