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

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use BabDev\PagerfantaBundle\BabDevPagerfantaBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle;
use Liip\TestFixturesBundle\LiipTestFixturesBundle;
use Meilisearch\Bundle\MeilisearchBundle;
use Payum\Bundle\PayumBundle\PayumBundle;
use Sentry\SentryBundle\SentryBundle;
use SolidInvoice\ApiBundle\SolidInvoiceApiBundle;
use SolidInvoice\ClientBundle\SolidInvoiceClientBundle;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\CronBundle\SolidInvoiceCronBundle;
use SolidInvoice\DashboardBundle\SolidInvoiceDashboardBundle;
use SolidInvoice\DataGridBundle\SolidInvoiceDataGridBundle;
use SolidInvoice\FormBundle\SolidInvoiceFormBundle;
use SolidInvoice\InstallBundle\SolidInvoiceInstallBundle;
use SolidInvoice\InvoiceBundle\SolidInvoiceInvoiceBundle;
use SolidInvoice\MailerBundle\SolidInvoiceMailerBundle;
use SolidInvoice\McpBundle\SolidInvoiceMcpBundle;
use SolidInvoice\MoneyBundle\SolidInvoiceMoneyBundle;
use SolidInvoice\NotificationBundle\SolidInvoiceNotificationBundle;
use SolidInvoice\PaymentBundle\SolidInvoicePaymentBundle;
use SolidInvoice\QuoteBundle\SolidInvoiceQuoteBundle;
use SolidInvoice\SaasBundle\SolidInvoiceSaasBundle;
use SolidInvoice\SettingsBundle\SolidInvoiceSettingsBundle;
use SolidInvoice\TaxBundle\SolidInvoiceTaxBundle;
use SolidInvoice\UserBundle\SolidInvoiceUserBundle;
use SolidWorx\Platform\SaasBundle\SolidWorxPlatformSaasBundle;
use SolidWorx\Platform\UiBundle\SolidWorxPlatformUiBundle;
use SolidWorx\Toggler\Symfony\TogglerBundle;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\UX\Autocomplete\AutocompleteBundle;
use Symfony\UX\Chartjs\ChartjsBundle;
use Symfony\UX\Dropzone\DropzoneBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TogglePassword\TogglePasswordBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;
use SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle;
use SymfonyCasts\Bundle\VerifyEmail\SymfonyCastsVerifyEmailBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;
use Zenstruck\Mailer\Test\ZenstruckMailerTestBundle;

$bundles = [
    FrameworkBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    WebpackEncoreBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    PayumBundle::class => ['all' => true],
    StofDoctrineExtensionsBundle::class => ['all' => true],
    ApiPlatformBundle::class => ['all' => true],
    SolidInvoiceApiBundle::class => ['all' => true],
    SolidInvoiceClientBundle::class => ['all' => true],
    SolidInvoiceCoreBundle::class => ['all' => true],
    SolidInvoiceCronBundle::class => ['all' => true],
    SolidInvoiceDashboardBundle::class => ['all' => true],
    SolidInvoiceDataGridBundle::class => ['all' => true],
    SolidInvoiceFormBundle::class => ['all' => true],
    SolidInvoiceInstallBundle::class => ['all' => true],
    SolidInvoiceInvoiceBundle::class => ['all' => true],
    SolidInvoiceMailerBundle::class => ['all' => true],
    SolidInvoiceMcpBundle::class => ['all' => true],
    SolidInvoiceMoneyBundle::class => ['all' => true],
    SolidInvoiceNotificationBundle::class => ['all' => true],
    SolidInvoicePaymentBundle::class => ['all' => true],
    SolidInvoiceQuoteBundle::class => ['all' => true],
    SolidInvoiceSettingsBundle::class => ['all' => true],
    SolidInvoiceTaxBundle::class => ['all' => true],
    SolidInvoiceUserBundle::class => ['all' => true],
    DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    LiipTestFixturesBundle::class => ['dev' => true, 'test' => true],
    DebugBundle::class => ['dev' => true],
    MakerBundle::class => ['dev' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
    TogglerBundle::class => ['all' => true],
    ZenstruckFoundryBundle::class => ['dev' => true, 'test' => true],
    SentryBundle::class => ['all' => true],
    DropzoneBundle::class => ['all' => true],
    StimulusBundle::class => ['all' => true],
    TwigComponentBundle::class => ['all' => true],
    LiveComponentBundle::class => ['all' => true],
    AutocompleteBundle::class => ['all' => true],
    BabDevPagerfantaBundle::class => ['all' => true],
    SymfonyCastsVerifyEmailBundle::class => ['all' => true],
    KnpUOAuth2ClientBundle::class => ['all' => true],
    TogglePasswordBundle::class => ['all' => true],
    SymfonyCastsResetPasswordBundle::class => ['all' => true],
    ZenstruckMailerTestBundle::class => ['dev' => true, 'test' => true],
    SolidWorxPlatformUiBundle::class => ['all' => true],
    ChartjsBundle::class => ['all' => true],
    MeilisearchBundle::class => ['all' => true],
    McpBundle::class => ['all' => true],
];

if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) === 'saas') {
    $bundles[SolidWorxPlatformSaasBundle::class] = ['all' => true];
    $bundles[SolidInvoiceSaasBundle::class] = ['all' => true];
}

return $bundles;
