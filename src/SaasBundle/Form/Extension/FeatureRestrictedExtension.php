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

namespace SolidInvoice\SaasBundle\Form\Extension;

use SolidInvoice\SaasBundle\Feature\RequiredPlanLabelProvider;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Disables form fields that have a `feature_gated` option pointing at a feature
 * key that is currently disabled on the company's plan, and exposes view vars
 * (`feature_gated_active`, `feature_gated_plan`) so templates can render an
 * upgrade badge with the plan name where the feature becomes available.
 *
 * Independent of the `trial_restricted` mechanism: a single Config can carry
 * both keys. On self-hosted deployments this extension is shadowed by a no-op
 * declared in CoreBundle so the `feature_gated` option remains valid.
 * @see \SolidInvoice\SaasBundle\Tests\Form\Extension\FeatureRestrictedExtensionTest
 */
final class FeatureRestrictedExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly FeatureGate $featureGate,
        private readonly RequiredPlanLabelProvider $planLabelProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->isGated($options['feature_gated'])) {
            $builder->setDisabled(true);
        }
    }

    /**
     * @param FormInterface<mixed> $form
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $featureKey = $options['feature_gated'];

        if (! $this->isGated($featureKey)) {
            $view->vars['feature_gated_active'] = false;
            $view->vars['feature_gated_plan'] = null;

            return;
        }

        $view->vars['disabled'] = true;
        $view->vars['feature_gated_active'] = true;
        $view->vars['feature_gated_plan'] = $this->planLabelProvider->menuLabel($featureKey);
        $view->vars['attr']['class'] = trim(($view->vars['attr']['class'] ?? '') . ' feature-gated');

        if (isset($view->vars['checked'])) {
            $view->vars['checked'] = false;
        }
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

    private function isGated(mixed $featureKey): bool
    {
        return is_string($featureKey) && $featureKey !== '' && ! $this->featureGate->isEnabled($featureKey);
    }
}
