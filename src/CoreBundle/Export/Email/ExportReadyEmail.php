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

namespace SolidInvoice\CoreBundle\Export\Email;

use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class ExportReadyEmail extends TemplatedEmail
{
    public function __construct(
        private readonly ExportJob $job,
        private readonly User $user,
        private readonly string $downloadUrl,
    ) {
        parent::__construct();

        $this->subject('Your data export is ready');
        $this->htmlTemplate('@SolidInvoiceCore/Email/export_ready.html.twig');
        $this->textTemplate('@SolidInvoiceCore/Email/export_ready.text.twig');
        $this->context([
            'job' => $this->job,
            'user' => $this->user,
            'downloadUrl' => $this->downloadUrl,
        ]);
    }

    public function getJob(): ExportJob
    {
        return $this->job;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }
}
