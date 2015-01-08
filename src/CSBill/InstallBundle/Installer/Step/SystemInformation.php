<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer\Step;

use CSBill\CoreBundle\CSBillCoreBundle;
use CSBill\CoreBundle\Repository\VersionRepository;
use CSBill\InstallBundle\Form\Step\SystemInformationForm;
use CSBill\InstallBundle\Installer\AbstractFormStep;
use CSBill\UserBundle\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class SystemInformation extends AbstractFormStep
{
    /**
     * @return SystemInformationForm|\Symfony\Component\Form\AbstractType
     */
    public function getForm()
    {
        return new SystemInformationForm();
    }

    /**
     * Save system and user configuration values
     */
    public function process()
    {
        $form = $this->buildForm();
        $data = $form->getData();

        $user = new User();

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);

        $password = $encoder->encodePassword($data['password'], $user->getSalt());

        $user->setUsername($data['username'])
            ->setEmail($data['email_address'])
            ->setPassword($password)
            ->setEnabled(true)
            ->setSuperAdmin(true);

        $em = $this->container->get('doctrine.orm.entity_manager');

        $em->persist($user);
        $em->flush();

        $this->saveConfig($data);
        $this->saveCurrentVersion();
    }

    /**
     * @param  array             $data
     * @throws \RuntimeException
     */
    protected function saveConfig(array $data)
    {
        $rootDir = $this->container->get('kernel')->getRootDir();

        $config = $rootDir.'/config/parameters.yml';

        $yaml = new Parser();

        try {
            $value = $yaml->parse(file_get_contents($config));
        } catch (ParseException $exception) {
            throw new \RuntimeException(
                sprintf(
                    "Unable to parse the YAML string: %s. Your installation might be corrupt.",
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }

        $value['parameters']['locale'] = $data['locale'];
        $value['parameters']['currency'] = $data['currency'];

        $dumper = new Dumper();

        $yaml = $dumper->dump($value, 2);

        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($config, $yaml);
    }

    /**
     * Saves the current app version in the database
     */

    protected function saveCurrentVersion()
    {
        $verion = CSBillCoreBundle::VERSION;

        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository('CSBillCoreBundle:Version');

        $repository->updateVersion($verion);
    }
}
