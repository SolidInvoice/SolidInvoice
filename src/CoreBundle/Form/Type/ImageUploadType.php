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

namespace SolidInvoice\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            private $file;

            public function transform($value)
            {
                if (null !== $value) {
                    $this->file = $value;
                }

                return new File('', false);
            }

            public function reverseTransform($value)
            {
                if (null === $value && null !== $this->file) {
                    return $this->file;
                }

                if (!$value instanceof UploadedFile) {
                    return;
                }

                if (!$value->isValid()) {
                    throw new TransformationFailedException();
                }

                return $value->guessExtension().'|'.base64_encode(file_get_contents($value->getPathname()));
            }
        });
    }

    public function getParent(): string
    {
        return FileType::class;
    }

    public function getBlockPrefix()
    {
        return 'image_upload';
    }
}
