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

namespace SolidInvoice\CoreBundle\Tests\Templating;

use InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Templating\BillingTemplateResolver;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SourcePolicyInterface;
use Twig\Source;
use Twig\TemplateWrapper;

final class BillingTemplateResolverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testResolveTemplateReturnsDefaultPathWhenNoActiveTemplate(): void
    {
        $repository = M::mock(BillingTemplateRepository::class);
        $repository->shouldReceive('findActive')
            ->with('invoice', 'pdf')
            ->andReturn(null);

        $resolver = new BillingTemplateResolver($repository);

        self::assertSame(
            '@SolidInvoiceInvoice/Pdf/invoice.html.twig',
            $resolver->resolveTemplate($this->makeEnvironment(), 'invoice', 'pdf')
        );
    }

    public function testResolveTemplateReturnsDefaultPathWhenActiveTemplateIsBlank(): void
    {
        $template = (new BillingTemplate())
            ->setType(BillingTemplate::TYPE_QUOTE)
            ->setVariant(BillingTemplate::VARIANT_EMAIL)
            ->setName('Blank')
            ->setContent('   ')
            ->setActive(true);

        $repository = M::mock(BillingTemplateRepository::class);
        $repository->shouldReceive('findActive')
            ->with('quote', 'email')
            ->andReturn($template);

        $resolver = new BillingTemplateResolver($repository);

        self::assertSame(
            '@SolidInvoiceQuote/Email/quote.html.twig',
            $resolver->resolveTemplate($this->makeEnvironment(), 'quote', 'email')
        );
    }

    public function testResolveTemplateWrapsCustomTemplateWithSandboxPrefix(): void
    {
        $template = (new BillingTemplate())
            ->setType(BillingTemplate::TYPE_INVOICE)
            ->setVariant(BillingTemplate::VARIANT_HTML)
            ->setName('Custom')
            ->setContent('Hello {{ invoice.invoiceId }}')
            ->setActive(true);

        $repository = M::mock(BillingTemplateRepository::class);
        $repository->shouldReceive('findActive')
            ->with('invoice', 'html')
            ->andReturn($template);

        $resolver = new BillingTemplateResolver($repository);

        $resolved = $resolver->resolveTemplate($this->makeEnvironment(), 'INVOICE', 'HTML');

        self::assertInstanceOf(TemplateWrapper::class, $resolved);
        self::assertStringStartsWith(BillingTemplateResolver::SANDBOX_NAME_PREFIX, $resolved->getSourceContext()->getName());
    }

    public function testResolveTemplateThrowsForUnknownType(): void
    {
        $repository = M::mock(BillingTemplateRepository::class);
        $resolver = new BillingTemplateResolver($repository);

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolveTemplate($this->makeEnvironment(), 'unknown', 'html');
    }

    public function testResolveTemplateThrowsForUnknownVariant(): void
    {
        $repository = M::mock(BillingTemplateRepository::class);
        $resolver = new BillingTemplateResolver($repository);

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolveTemplate($this->makeEnvironment(), 'invoice', 'unknown');
    }

    public function testGetDefaultTemplatePath(): void
    {
        $repository = M::mock(BillingTemplateRepository::class);
        $resolver = new BillingTemplateResolver($repository);

        self::assertSame(
            '@SolidInvoiceInvoice/Email/invoice.html.twig',
            $resolver->getDefaultTemplatePath('invoice', 'email')
        );
    }

    public function testSandboxBlocksDangerousFunctions(): void
    {
        $environment = $this->makeEnvironment();
        $environment->addExtension(new SandboxExtension(
            BillingTemplateResolver::createSecurityPolicy(),
            true,
        ));

        $this->expectException(SecurityError::class);
        $this->expectExceptionMessageMatches('/constant/');

        $template = $environment->createTemplate(
            "{{ constant('PHP_VERSION') }}",
            BillingTemplateResolver::SANDBOX_NAME_PREFIX . 'test'
        );
        $template->render([]);
    }

    public function testSandboxBlocksDumpFunction(): void
    {
        $environment = $this->makeEnvironment();
        $environment->addExtension(new SandboxExtension(
            BillingTemplateResolver::createSecurityPolicy(),
            true,
        ));
        $environment->addExtension(new DebugExtension());

        $this->expectException(SecurityError::class);
        $this->expectExceptionMessageMatches('/dump/');

        $template = $environment->createTemplate(
            '{{ dump(secret) }}',
            BillingTemplateResolver::SANDBOX_NAME_PREFIX . 'test'
        );
        $template->render(['secret' => 'value']);
    }

    public function testSandboxBlocksArbitraryMethodOnExposedObject(): void
    {
        $environment = $this->makeEnvironment();
        $environment->addExtension(new SandboxExtension(
            BillingTemplateResolver::createSecurityPolicy(),
            true,
        ));

        $object = new class() {
            public function getSensitive(): string
            {
                return 'leak';
            }
        };

        $template = $environment->createTemplate(
            '{{ obj.getSensitive() }}',
            BillingTemplateResolver::SANDBOX_NAME_PREFIX . 'test'
        );

        $this->expectException(SecurityError::class);

        $template->render(['obj' => $object]);
    }

    public function testSandboxRejectsRawFilter(): void
    {
        $environment = $this->makeEnvironment();
        $environment->addExtension(new SandboxExtension(
            BillingTemplateResolver::createSecurityPolicy(),
            true,
        ));

        $this->expectException(SecurityError::class);
        $this->expectExceptionMessageMatches('/raw/');

        $template = $environment->createTemplate(
            "{{ '<b>html</b>'|raw }}",
            BillingTemplateResolver::SANDBOX_NAME_PREFIX . 'test'
        );
        $template->render([]);
    }

    public function testSourcePolicyOnlySandboxesPrefixedTemplates(): void
    {
        $repository = M::mock(BillingTemplateRepository::class);
        $resolver = new BillingTemplateResolver($repository);

        $sandbox = $resolver->createSandboxExtension();
        $sourcePolicy = $this->extractSourcePolicy($sandbox);

        self::assertTrue($sourcePolicy->enableSandbox(new Source('x', BillingTemplateResolver::SANDBOX_NAME_PREFIX . 'invoice_html_abc')));
        self::assertFalse($sourcePolicy->enableSandbox(new Source('x', 'random/template.html.twig')));
    }

    public function testRenderPreviewWrapsContentWithSandboxPrefix(): void
    {
        $repository = M::mock(BillingTemplateRepository::class);
        $resolver = new BillingTemplateResolver($repository);

        $environment = $this->makeEnvironment();
        $environment->addExtension(new SandboxExtension(
            BillingTemplateResolver::createSecurityPolicy(),
            false,
            new class() implements SourcePolicyInterface {
                public function enableSandbox(Source $source): bool
                {
                    return str_starts_with($source->getName(), BillingTemplateResolver::SANDBOX_NAME_PREFIX);
                }
            },
        ));

        $output = $resolver->renderPreview($environment, 'invoice', 'html', 'Total: {{ total }}', ['total' => '42']);

        self::assertSame('Total: 42', $output);
    }

    private function makeEnvironment(): Environment
    {
        return new Environment(new ArrayLoader());
    }

    private function extractSourcePolicy(SandboxExtension $extension): SourcePolicyInterface
    {
        $reflection = new ReflectionClass($extension);
        $property = $reflection->getProperty('sourcePolicy');
        $property->setAccessible(true);

        return $property->getValue($extension);
    }
}
