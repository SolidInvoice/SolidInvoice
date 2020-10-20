<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::SETS, [
        // General
        SetList::PHPSTAN,
        SetList::CODE_QUALITY,

        // PHPUnit
        SetList::PHPUNIT_70,
        SetList::PHPUNIT_75,
        SetList::PHPUNIT_80,
        SetList::PHPUNIT_90,
        SetList::PHPUNIT_91,
        SetList::PHPUNIT_CODE_QUALITY,
        SetList::PHPUNIT_EXCEPTION,
        SetList::PHPUNIT_MOCK,
        SetList::PHPUNIT_YIELD_DATA_PROVIDER,

        // Doctrine
        SetList::DOCTRINE_25,
        SetList::DOCTRINE_COMMON_20,
        SetList::DOCTRINE_DBAL_30,
        SetList::DOCTRINE_SERVICES,
        SetList::DOCTRINE_CODE_QUALITY,

        // Symfony
        SetList::SYMFONY_40,
        SetList::SYMFONY_41,
        SetList::SYMFONY_42,
        SetList::SYMFONY_43,
        SetList::SYMFONY_44,
        SetList::SYMFONY_50,
        SetList::SYMFONY_CODE_QUALITY,
        SetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);

    $parameters->set(Option::PATHS, __DIR__.'/src');
    $parameters->set(Option::OPTION_AUTOLOAD_FILE, __DIR__.'/app/autoload.php');
    $parameters->set(Option::AUTOLOAD_PATHS, __DIR__.'/vendor/bin/.phpunit/phpunit-8.3-0/vendor/autoload.php');

    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, false);
    $parameters->set(Option::IMPORT_DOC_BLOCKS, false);
    $parameters->set(Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER, __DIR__.'/var/cache/dev/appAppKernelDevDebugContainer.xml');
    $parameters->set(Option::PHP_VERSION_FEATURES, '7.3');
    $parameters->set(Option::PROJECT_TYPE, Option::PROJECT_TYPE_OPEN_SOURCE);
};
