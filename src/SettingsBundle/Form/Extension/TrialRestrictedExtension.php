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

namespace SolidInvoice\SettingsBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TrialRestrictedExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['trial_restricted'] && $options['subscription_in_trial']) {
            $builder->setDisabled(true);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // If field is trial-restricted AND subscription is in trial, disable it
        if ($options['trial_restricted'] && $options['subscription_in_trial']) {
            $view->vars['disabled'] = true;
            $view->vars['trial_restricted_active'] = true;
            $view->vars['attr']['class'] = ($view->vars['attr']['class'] ?? '') . ' trial-restricted';

            // For checkboxes, force unchecked state visually
            if (isset($view->vars['checked'])) {
                $view->vars['checked'] = false;
            }
        } else {
            $view->vars['trial_restricted_active'] = false;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'trial_restricted' => false,
            'subscription_in_trial' => false,
        ]);

        $resolver->setAllowedTypes('trial_restricted', 'bool');
        $resolver->setAllowedTypes('subscription_in_trial', 'bool');
    }

    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }
}
