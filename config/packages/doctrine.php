<?php

declare(strict_types=1);

use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidType;
use SolidInvoice\CoreBundle\Doctrine\Filter\ArchivableFilter;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\MoneyBundle\Doctrine\Hydrator\MoneyHydrator;
use Symfony\Config\DoctrineConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (DoctrineConfig $config): void {

    $dbalConfig = $config->dbal();

    $ormConfig = $config->orm();

    $dbalConfig
        ->connection('default')
            ->driver(env('database_driver'))
            ->host(env('database_host'))
            ->port(env('database_port')->int())
            ->dbname(env('database_name'))
            ->user(env('database_user'))
            ->password(env('database_password'))
            ->serverVersion(env('database_version'))
            ->charset('UTF8');

    $dbalConfig
        ->type(UuidType::NAME)
        ->class(UuidType::class);

    $dbalConfig
        ->type(UuidBinaryOrderedTimeType::NAME)
        ->class(UuidBinaryOrderedTimeType::class);

    $ormConfig->autoGenerateProxyClasses(param('kernel.debug'));

    $entityManagerConfig = $ormConfig->entityManager('default');

    $entityManagerConfig
        ->hydrator('money', MoneyHydrator::class)
        ->filter('company', CompanyFilter::class)
        ->filter('archivable', ArchivableFilter::class)
        ->autoMapping(true);

    $entityManagerConfig->mapping('SolidInvoiceMoneyBundle')
        ->isBundle(false)
        ->dir(param('kernel.project_dir') . '/src/MoneyBundle/Entity')
        ->prefix('SolidInvoice\MoneyBundle\Entity')
        ->alias('SolidInvoiceMoney')
        ->type('attribute');

    $entityManagerConfig->mapping('payum')
        ->isBundle(false)
        ->type('xml')
        ->dir(param('kernel.project_dir') . '/vendor/payum/core/Payum/Core/Bridge/Doctrine/Resources/mapping')
        ->prefix('Payum\Core\Model');
};
