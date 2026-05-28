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

namespace SolidInvoice\SettingsBundle\Form\Type;

use SolidInvoice\CoreBundle\Validator\Constraints\ValidTwig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TemplateEditorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'attr' => [
                'rows' => 18,
                'spellcheck' => 'false',
                'data-controller' => 'code-editor',
                'data-code-editor-language-value' => 'html',
            ],
            'constraints' => [new ValidTwig()],
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
