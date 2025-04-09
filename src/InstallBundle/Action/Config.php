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

namespace SolidInvoice\InstallBundle\Action;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use PDO;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\Config\DatabaseConfig;
use SolidInvoice\InstallBundle\Doctrine\Drivers;
use SolidInvoice\InstallBundle\Form\Step\ConfigStepForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function assert;

final class Config extends AbstractController
{
    public function __construct(
        private readonly ConfigWriter $configWriter,
        #[Autowire(env: 'SOLIDINVOICE_CONFIG_DIR')]
        private readonly string $configDir
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(ConfigStepForm::class);

        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->handleForm($request, $form);
        }

        return $this->renderTemplate($form);
    }

    private function handleForm(Request $request, FormInterface $form): Response
    {
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $config = $data['database_config'];

            if ($config['driver'] === 'sqlite') {
                (new Filesystem())->mkdir($this->configDir . '/db');
                $config['name'] = $this->configDir . '/db/solidinvoice.db';
            }

            try {
                unset($data['database_config']['name']);
                $data['database_config']['driver'] = Drivers::getDriver($data['database_config']['driver']);
                $nativeConnection = DriverManager::getConnection($data['database_config'])->getNativeConnection();
            } catch (Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->renderTemplate($form);
            }

            assert($nativeConnection instanceof PDO);

            $config['version'] = $nativeConnection->getAttribute(PDO::ATTR_SERVER_VERSION);

            $this->configWriter->save(['database_url' => DatabaseConfig::paramsToDatabaseUrl($config)]);

            return $this->redirectToRoute('_install_install');
        }

        return $this->renderTemplate($form);
    }

    private function renderTemplate(FormInterface $form): Response
    {
        return $this->render(
            '@SolidInvoiceInstall/config.html.twig',
            [
                'form' => $form,
            ]
        );
    }
}
