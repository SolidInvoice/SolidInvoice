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

namespace SolidInvoice\InstallBundle\Form\FormFlow;

use Doctrine\DBAL\DriverManager;
use PDO;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\Config\DatabaseConfig;
use SolidInvoice\InstallBundle\Doctrine\Drivers;
use SolidInvoice\InstallBundle\DTO\Installation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Flow\AbstractButtonFlowType;
use Symfony\Component\Form\Flow\ButtonFlowInterface;
use Symfony\Component\Form\Flow\FormFlowCursor;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\Form\Flow\Type\NextFlowType;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;
use function assert;

final class InstallFlowType extends AbstractButtonFlowType
{
    public function __construct(
        private readonly ConfigWriter $systemConfigWriter,
        #[Autowire(env: 'SOLIDINVOICE_CONFIG_DIR')]
        private readonly string $configDir
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'handler' => function (mixed $data, ButtonFlowInterface $button, FormFlowInterface $flow): void {
                /** @var Installation $formData */
                $formData = $flow->getData();

                if ($formData->databaseConfig->driver === 'sqlite') {
                    try {
                        (new Filesystem())->mkdir($this->configDir . '/db');
                        $formData->databaseConfig->name = $this->configDir . '/db/solidinvoice.db';
                    } catch (IOException $e) {
                        $flow->addError(new FormError($e->getMessage()));
                        return;
                    }
                }

                try {
                    $dbOptions = (array) $formData->databaseConfig;
                    unset($dbOptions['name']);

                    $nativeConnection = DriverManager::getConnection(['driver' => Drivers::getDriver($dbOptions['driver'])] + $dbOptions)->getNativeConnection();

                    assert($nativeConnection instanceof PDO);

                    $dbOptions['version'] = $nativeConnection->getAttribute(PDO::ATTR_SERVER_VERSION);
                    $dbOptions['name'] = $formData->databaseConfig->name;

                    $this->systemConfigWriter->save(['database_url' => DatabaseConfig::paramsToDatabaseUrl($dbOptions)]);

                    $flow->moveNext();
                } catch (Throwable $e) {
                    $flow->addError(new FormError($e->getMessage()));
                }
            },
            'include_if' => fn (FormFlowCursor $cursor): bool => $cursor->getCurrentStep() === 'review' && $cursor->canMoveNext(),
        ]);
    }

    public function getParent(): string
    {
        return NextFlowType::class;
    }
}
