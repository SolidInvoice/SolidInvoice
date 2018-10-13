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

use SolidInvoice\CoreBundle\Kernel\ContainerClassKernelInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel implements ContainerClassKernelInterface
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new Bazinga\Bundle\JsTranslationBundle\BazingaJsTranslationBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Oro\Bundle\RequireJSBundle\OroRequireJSBundle(),
            new Payum\Bundle\PayumBundle\PayumBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Sylius\Bundle\FlowBundle\SyliusFlowBundle(),
            new SolidWorx\FormHandler\FormHandlerBundle(),
            new ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle(),

            new SolidInvoice\ApiBundle\SolidInvoiceApiBundle(),
            new SolidInvoice\ClientBundle\SolidInvoiceClientBundle(),
            new SolidInvoice\CoreBundle\SolidInvoiceCoreBundle(),
            new SolidInvoice\CronBundle\SolidInvoiceCronBundle(),
            new SolidInvoice\DashboardBundle\SolidInvoiceDashboardBundle(),
            new SolidInvoice\DataGridBundle\SolidInvoiceDataGridBundle($this),
            new SolidInvoice\FormBundle\SolidInvoiceFormBundle(),
            new SolidInvoice\InstallBundle\SolidInvoiceInstallBundle(),
            new SolidInvoice\InvoiceBundle\SolidInvoiceInvoiceBundle(),
            new SolidInvoice\MenuBundle\SolidInvoiceMenuBundle(),
            new SolidInvoice\MoneyBundle\SolidInvoiceMoneyBundle(),
            new SolidInvoice\NotificationBundle\SolidInvoiceNotificationBundle(),
            new SolidInvoice\PaymentBundle\SolidInvoicePaymentBundle(),
            new SolidInvoice\QuoteBundle\SolidInvoiceQuoteBundle(),
            new SolidInvoice\SettingsBundle\SolidInvoiceSettingsBundle(),
            new SolidInvoice\TaxBundle\SolidInvoiceTaxBundle(),
            new SolidInvoice\UserBundle\SolidInvoiceUserBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return __DIR__;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigDir(): string
    {
        return $this->getRootDir().'/config';
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerCacheClass(): string
    {
        return $this->getContainerClass();
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
