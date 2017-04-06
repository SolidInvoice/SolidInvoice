<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use CSBill\CoreBundle\Kernel\ContainerClassKernelInterface;
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

            new Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle(),
            new Bazinga\Bundle\JsTranslationBundle\BazingaJsTranslationBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Finite\Bundle\FiniteBundle\FiniteFiniteBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new Oro\Bundle\RequireJSBundle\OroRequireJSBundle(),
            new Payum\Bundle\PayumBundle\PayumBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Sylius\Bundle\FlowBundle\SyliusFlowBundle(),
            new SolidWorx\FormHandler\FormHandlerBundle(),

            new CSBill\ApiBundle\CSBillApiBundle(),
            new CSBill\ClientBundle\CSBillClientBundle(),
            new CSBill\CoreBundle\CSBillCoreBundle(),
            new CSBill\CronBundle\CSBillCronBundle(),
            new CSBill\DashboardBundle\CSBillDashboardBundle(),
            new CSBill\DataGridBundle\CSBillDataGridBundle($this),
            new CSBill\FormBundle\CSBillFormBundle(),
            new CSBill\InstallBundle\CSBillInstallBundle(),
            new CSBill\InvoiceBundle\CSBillInvoiceBundle(),
            new CSBill\ItemBundle\CSBillItemBundle(),
            new CSBill\MenuBundle\CSBillMenuBundle(),
            new CSBill\MoneyBundle\CSBillMoneyBundle(),
            new CSBill\NotificationBundle\CSBillNotificationBundle(),
            new CSBill\PaymentBundle\CSBillPaymentBundle(),
            new CSBill\QuoteBundle\CSBillQuoteBundle(),
            new CSBill\SettingsBundle\CSBillSettingsBundle(),
            new CSBill\TaxBundle\CSBillTaxBundle(),
            new CSBill\UserBundle\CSBillUserBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
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
