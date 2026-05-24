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

namespace SolidInvoice\SaasBundle\Tests\Form\Extension;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Feature\RequiredPlanLabelProvider;
use SolidInvoice\SaasBundle\Form\Extension\FeatureRestrictedExtension;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[CoversClass(FeatureRestrictedExtension::class)]
final class FeatureRestrictedExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildViewDisablesFieldWhenFeatureIsDisabled(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with('custom_branding')->andReturnFalse();

        $renderer = M::mock(RequiredPlanLabelProvider::class);
        $renderer->shouldReceive('menuLabel')->with('custom_branding')->andReturn('Solo');

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $view = new FormView();
        $extension->buildView($view, $this->createStub(FormInterface::class), [
            'feature_gated' => 'custom_branding',
        ]);

        self::assertTrue($view->vars['disabled']);
        self::assertTrue($view->vars['feature_gated_active']);
        self::assertSame('Solo', $view->vars['feature_gated_plan']);
        self::assertStringContainsString('feature-gated', (string) $view->vars['attr']['class']);
    }

    public function testBuildViewIsNoOpWhenFeatureIsEnabled(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with('custom_branding')->andReturnTrue();

        $renderer = M::mock(RequiredPlanLabelProvider::class);
        $renderer->shouldNotReceive('menuLabel');

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $view = new FormView();
        $extension->buildView($view, $this->createStub(FormInterface::class), [
            'feature_gated' => 'custom_branding',
        ]);

        self::assertArrayNotHasKey('disabled', $view->vars);
        self::assertFalse($view->vars['feature_gated_active']);
        self::assertNull($view->vars['feature_gated_plan']);
    }

    public function testBuildViewIsNoOpWhenOptionIsNull(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldNotReceive('isEnabled');

        $renderer = M::mock(RequiredPlanLabelProvider::class);
        $renderer->shouldNotReceive('menuLabel');

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $view = new FormView();
        $extension->buildView($view, $this->createStub(FormInterface::class), [
            'feature_gated' => null,
        ]);

        self::assertFalse($view->vars['feature_gated_active']);
        self::assertNull($view->vars['feature_gated_plan']);
    }

    public function testBuildViewIsNoOpWhenOptionIsFalse(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldNotReceive('isEnabled');

        $renderer = M::mock(RequiredPlanLabelProvider::class);

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $view = new FormView();
        $extension->buildView($view, $this->createStub(FormInterface::class), [
            'feature_gated' => false,
        ]);

        self::assertFalse($view->vars['feature_gated_active']);
        self::assertNull($view->vars['feature_gated_plan']);
    }

    public function testBuildViewForcesCheckboxUnchecked(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with('custom_branding')->andReturnFalse();

        $renderer = M::mock(RequiredPlanLabelProvider::class);
        $renderer->shouldReceive('menuLabel')->with('custom_branding')->andReturn('Business');

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $view = new FormView();
        $view->vars['checked'] = true;

        $extension->buildView($view, $this->createStub(FormInterface::class), [
            'feature_gated' => 'custom_branding',
        ]);

        self::assertFalse($view->vars['checked']);
    }

    public function testBuildViewPreservesExistingAttrClass(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with('custom_branding')->andReturnFalse();

        $renderer = M::mock(RequiredPlanLabelProvider::class);
        $renderer->shouldReceive('menuLabel')->with('custom_branding')->andReturn(null);

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $view = new FormView();
        $view->vars['attr']['class'] = 'existing-class';

        $extension->buildView($view, $this->createStub(FormInterface::class), [
            'feature_gated' => 'custom_branding',
        ]);

        self::assertStringContainsString('existing-class', (string) $view->vars['attr']['class']);
        self::assertStringContainsString('feature-gated', (string) $view->vars['attr']['class']);
        self::assertNull($view->vars['feature_gated_plan']);
    }

    public function testBuildFormDisablesBuilderWhenGated(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with('custom_branding')->andReturnFalse();

        $renderer = M::mock(RequiredPlanLabelProvider::class);

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $builder = M::mock(FormBuilderInterface::class);
        $builder->shouldReceive('setDisabled')->with(true)->once();

        $extension->buildForm($builder, ['feature_gated' => 'custom_branding']);
    }

    public function testBuildFormDoesNotDisableBuilderWhenFeatureEnabled(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('isEnabled')->with('custom_branding')->andReturnTrue();

        $renderer = M::mock(RequiredPlanLabelProvider::class);

        $extension = new FeatureRestrictedExtension($gate, $renderer);

        $builder = M::mock(FormBuilderInterface::class);
        $builder->shouldNotReceive('setDisabled');

        $extension->buildForm($builder, ['feature_gated' => 'custom_branding']);
    }

    public function testConfigureOptionsDefaultsToNull(): void
    {
        $extension = new FeatureRestrictedExtension(
            M::mock(FeatureGate::class),
            M::mock(RequiredPlanLabelProvider::class),
        );

        $resolver = new OptionsResolver();
        $extension->configureOptions($resolver);

        self::assertNull($resolver->resolve([])['feature_gated']);
    }

    public function testConfigureOptionsAcceptsString(): void
    {
        $extension = new FeatureRestrictedExtension(
            M::mock(FeatureGate::class),
            M::mock(RequiredPlanLabelProvider::class),
        );

        $resolver = new OptionsResolver();
        $extension->configureOptions($resolver);

        self::assertSame('foo', $resolver->resolve(['feature_gated' => 'foo'])['feature_gated']);
    }

    public function testConfigureOptionsRejectsInts(): void
    {
        $extension = new FeatureRestrictedExtension(
            M::mock(FeatureGate::class),
            M::mock(RequiredPlanLabelProvider::class),
        );

        $resolver = new OptionsResolver();
        $extension->configureOptions($resolver);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['feature_gated' => 123]);
    }

    public function testGetExtendedTypesReturnsFormType(): void
    {
        $types = iterator_to_array(FeatureRestrictedExtension::getExtendedTypes());

        self::assertCount(1, $types);
        self::assertSame(FormType::class, $types[0]);
    }
}
