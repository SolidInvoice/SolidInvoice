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

namespace SolidInvoice\CoreBundle\Form\Type;

use Override;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Form\Type\ImageUploadTypeTest
 * @extends AbstractType<mixed>
 */
class ImageUploadType extends AbstractType
{
    /**
     * Allowed raster image MIME types. SVG is intentionally excluded as it can
     * contain executable JavaScript which would result in stored XSS when the
     * logo is rendered inline as a data: URI.
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $validator = $this->validator;

        $builder->addModelTransformer(new class($validator) implements DataTransformerInterface {
            private ?string $file = null;

            public function __construct(
                private readonly ValidatorInterface $validator
            ) {
            }

            public function transform(mixed $value): File
            {
                if ($value instanceof Setting) {
                    $this->file = $value->getValue();
                }

                return new File('', false);
            }

            public function reverseTransform(mixed $value): ?string
            {
                if (null === $value && null !== $this->file) {
                    return $this->file;
                }

                if (! $value instanceof UploadedFile) {
                    return null;
                }

                if (! $value->isValid()) {
                    throw new TransformationFailedException($value->getErrorMessage());
                }

                $violations = $this->validator->validate($value, new FileConstraint(
                    mimeTypes: ImageUploadType::ALLOWED_MIME_TYPES,
                    mimeTypesMessage: 'The uploaded file must be a JPEG, PNG, GIF or WebP image.',
                ));

                if (count($violations) > 0) {
                    $exception = new TransformationFailedException($violations[0]->getMessage());
                    $exception->setInvalidMessage($violations[0]->getMessage());

                    throw $exception;
                }

                if (false === @getimagesize($value->getPathname())) {
                    $message = 'The uploaded file is not a valid image.';
                    $exception = new TransformationFailedException($message);
                    $exception->setInvalidMessage($message);

                    throw $exception;
                }

                return $value->guessExtension() . '|' . base64_encode(file_get_contents($value->getPathname()));
            }
        });
    }

    #[Override]
    public function getParent(): string
    {
        return DropzoneType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'image_upload';
    }
}
