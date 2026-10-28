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

namespace SolidInvoice\ApiBundle\Action\Company;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Validator\Exception\ValidationException;
use InvalidArgumentException;
use SolidInvoice\ApiBundle\ApiResource\CompanyProfile;
use SolidInvoice\ApiBundle\State\Provider\CompanyProfileProvider;
use SolidInvoice\CoreBundle\Company\CompanyLogo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;
use function file_get_contents;

/**
 * POST /api/company/logo: the `file` field of a multipart/form-data request.
 */
#[AsController]
final readonly class UploadCompanyLogo
{
    public function __construct(
        private CompanyLogo $logo,
        private CompanyProfileProvider $provider,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): CompanyProfile
    {
        $file = $request->files->get('file');

        try {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw new InvalidArgumentException('No file was uploaded.');
            }

            $this->logo->store((string) file_get_contents($file->getPathname()));
        } catch (InvalidArgumentException) {
            throw new ValidationException(new ConstraintViolationList([
                new ConstraintViolation($this->translator->trans('core.image_upload.invalid_logo', [], 'validators'), null, [], null, 'file', null),
            ]));
        }

        return $this->provider->provide(new Get());
    }
}
