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

namespace SolidInvoice\CoreBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Self-hosted no-op for the `feature_gated` form option that is registered by
 * SaasBundle's FeatureRestrictedExtension when the SaaS bundle is loaded.
 *
 * Self-hosted deployments do not load SaasBundle, so this extension keeps the
 * option valid (default null) and ensures `feature_gated_active`/`_plan` view
 * vars exist with safe defaults — letting templates render the same partials
 * uniformly. Conditionally registered in CoreBundle's services.php only when
 * `SOLIDINVOICE_PLATFORM` is not `saas`.
 */
final class FeatureRestrictedExtension extends AbstractTypeExtension
{
    /**
     * @param FormInterface<mixed> $form
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['feature_gated_active'] = false;
        $view->vars['feature_gated_plan'] = null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('feature_gated', null);
        $resolver->setAllowedTypes('feature_gated', ['null', 'string', 'bool']);
    }

    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }
}
