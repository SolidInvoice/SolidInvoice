<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Installer\Step;

use CSBill\InstallBundle\Form\Step\SystemInformationForm;
use CSBill\InstallBundle\Installer\AbstractFormStep;
use CSBill\UserBundle\Entity\User;
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

        $user = new User;

        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);

        $password = $encoder->encodePassword($data['password'], $user->getSalt());

        $user->setUsername($data['username'])
             ->setEmail($data['email_address'])
             ->setPassword($password);

        $em = $this->container->get('doctrine.orm.entity_manager');

        $role = $em->getRepository('CSBillUserBundle:Role')->findOneBy(array('name' => 'super_admin'));

        $user->addRole($role);

        $em->persist($user);
        $em->flush();


        $this->saveConfig($data);
    }

    /**
     * @param array $data
     * @throws \RuntimeException
     * @TODO This section needs to move to a central location (along with databse config)
     */
    protected function saveConfig(array $data)
    {
        $rootDir = $this->container->get('kernel')->getRootDir();

        $config = $rootDir . '/config/parameters.yml';

        $yaml = new Parser();

        try {
            $value = $yaml->parse(file_get_contents($config));
        } catch (ParseException $e) {
            throw new \RuntimeException(
                "Unable to parse the YAML string: %s. Your installation might be corrupt.",
                $e->getCode(),
                $e
            );
        }

        $value['parameters']['locale'] = $data['locale'];
        $value['parameters']['currency'] = $data['currency'];

        $dumper = new Dumper();

        $yaml = $dumper->dump($value, 2);

        file_put_contents($config, $yaml);
    }
}
