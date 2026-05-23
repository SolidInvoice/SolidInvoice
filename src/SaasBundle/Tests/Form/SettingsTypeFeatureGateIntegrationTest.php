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

namespace SolidInvoice\SaasBundle\Tests\Form;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SaasBundle\Feature\RequiredPlanLabelProvider;
use SolidInvoice\SaasBundle\Form\Extension\FeatureRestrictedExtension;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Extension\CheckBoxExtension;
use SolidInvoice\SettingsBundle\Form\Extension\TrialRestrictedExtension;
use SolidInvoice\SettingsBundle\Form\Type\SettingsType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

/**
 * End-to-end test that wires SettingsType with both restriction extensions and
 * exercises the `feature_gated` Config flow on a Free-plan company:
 *  - the disabled badge view var is set
 *  - the plan name is exposed for the badge label
 *  - the trial-restricted mechanism is unaffected by the new option
 */
final class SettingsTypeFeatureGateIntegrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFeatureGatedFieldExposesActiveAndPlanViewVars(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with(Feature::CustomBranding->value)->andReturnFalse();

        $renderer = M::mock(RequiredPlanLabelProvider::class);
        $renderer->shouldReceive('menuLabel')->with(Feature::CustomBranding->value)->andReturn('Solo');

        $factory = $this->createFormFactory($gate, $renderer);

        $setting = $this->buildSetting(['feature_gated' => Feature::CustomBranding->value]);

        $form = $factory->create(SettingsType::class, null, [
            'settings' => ['hide_powered_by' => $setting],
        ]);

        $view = $form->createView();
        $field = $view->children['hide_powered_by'];

        self::assertTrue($field->vars['feature_gated_active']);
        self::assertSame('Solo', $field->vars['feature_gated_plan']);
        self::assertTrue($field->vars['disabled']);
    }

    public function testEnabledFeatureLeavesFieldNormal(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with(Feature::CustomBranding->value)->andReturnTrue();

        $renderer = M::mock(RequiredPlanLabelProvider::class);

        $factory = $this->createFormFactory($gate, $renderer);

        $setting = $this->buildSetting(['feature_gated' => Feature::CustomBranding->value]);

        $form = $factory->create(SettingsType::class, null, [
            'settings' => ['hide_powered_by' => $setting],
        ]);

        $view = $form->createView();
        $field = $view->children['hide_powered_by'];

        self::assertFalse($field->vars['feature_gated_active']);
        self::assertNull($field->vars['feature_gated_plan']);
        self::assertFalse($field->vars['disabled']);
    }

    public function testFeatureGatedAndTrialRestrictedAreIndependent(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with(Feature::CustomBranding->value)->andReturnFalse();

        $renderer = M::mock(RequiredPlanLabelProvider::class);
        $renderer->shouldReceive('menuLabel')->with(Feature::CustomBranding->value)->andReturn('Business');

        $factory = $this->createFormFactory($gate, $renderer);

        $setting = $this->buildSetting([
            'feature_gated' => Feature::CustomBranding->value,
            'trial_restricted' => true,
        ]);

        $form = $factory->create(SettingsType::class, null, [
            'settings' => ['hide_powered_by' => $setting],
            'subscription_in_trial' => true,
        ]);

        $view = $form->createView();
        $field = $view->children['hide_powered_by'];

        self::assertTrue($field->vars['feature_gated_active']);
        self::assertSame('Business', $field->vars['feature_gated_plan']);
        self::assertTrue($field->vars['trial_restricted_active']);
        self::assertTrue($field->vars['disabled']);
    }

    public function testNoFlagsLeavesFieldUntouched(): void
    {
        $gate = M::mock(FeatureGate::class);

        $renderer = M::mock(RequiredPlanLabelProvider::class);

        $factory = $this->createFormFactory($gate, $renderer);

        $setting = $this->buildSetting([]);

        $form = $factory->create(SettingsType::class, null, [
            'settings' => ['hide_powered_by' => $setting],
        ]);

        $view = $form->createView();
        $field = $view->children['hide_powered_by'];

        self::assertFalse($field->vars['feature_gated_active']);
        self::assertNull($field->vars['feature_gated_plan']);
        self::assertFalse($field->vars['trial_restricted_active']);
        self::assertFalse($field->vars['disabled']);
    }

    /**
     * @param array<string, mixed> $formOptions
     */
    private function buildSetting(array $formOptions): Setting
    {
        $setting = new Setting();
        $setting->setKey('hide_powered_by');
        $setting->setType(CheckboxType::class);
        $setting->setValue('0');
        $setting->setDefaultValue('0');
        $setting->setFormOptions($formOptions);

        return $setting;
    }

    private function createFormFactory(FeatureGate $gate, RequiredPlanLabelProvider $renderer): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addTypeExtensions([
                new TrialRestrictedExtension(),
                new FeatureRestrictedExtension($gate, $renderer),
                new CheckBoxExtension(),
            ])
            ->getFormFactory();
    }
}
