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

namespace SolidInvoice\InstallBundle\Process\Step;

use DateTime;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use InvalidArgumentException;
use Mpociot\VatCalculator\VatCalculator;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\InstallBundle\Form\Step\SystemInformationForm;
use SolidInvoice\MoneyBundle\Factory\CurrencyFactory;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Step\AbstractControllerStep;
use Symfony\Component\DependencyInjection\Exception as DependencyInjectionException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class SetupStep extends AbstractControllerStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $form = $this->getForm($context->getRequest());

        return $this->render(
            '@SolidInvoiceInstall/Flow/setup.html.twig',
            [
                'form' => $form->createView(),
                'userCount' => $this->getUserCount(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return FormInterface
     */
    private function getForm(Request $request): FormInterface
    {
        $options = [
            'action' => $this->generateUrl(
                'sylius_flow_forward',
                [
                    'scenarioAlias' => 'install',
                    'stepName' => 'setup',
                ]
            ),
            'userCount' => $this->getUserCount(),
        ];

        $data = [
            'base_url' => $request->getSchemeAndHttpHost().$request->getBaseUrl(),
        ];

        return $this->createForm(SystemInformationForm::class, $data, $options);
    }

    /**
     * @return int
     */
    private function getUserCount()
    {
        static $userCount;

        if (null === $userCount) {
            $entityManager = $this->container->get('doctrine');

            /** @var UserRepository $repository */
            $repository = $entityManager->getRepository(User::class);

            $userCount = $repository->getUserCount();
        }

        return $userCount;
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(ProcessContextInterface $context)
    {
        $request = $context->getRequest();
        $form = $this->getForm($context->getRequest());

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            if (0 === $this->getUserCount()) {
                $this->createAdminUser($data);
            }

            $this->saveCurrentVersion();
            $this->saveConfig($data);

            return $this->complete();
        }

        return $this->render(
            '@SolidInvoiceInstall/Flow/setup.html.twig',
            [
                'form' => $form->createView(),
                'userCount' => $this->getUserCount(),
            ]
        );
    }

    /**
     * @param array $data
     */
    private function createAdminUser(array $data)
    {
        $user = new User();

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);

        $password = $encoder->encodePassword($data['password'], null);

        $user->setUsername($data['username'])
            ->setEmail($data['email_address'])
            ->setPassword($password)
            ->setEnabled(true)
            ->setSuperAdmin(true);

        $entityManager = $this->container->get('doctrine')->getManager();

        $entityManager->persist($user);
        $entityManager->flush();
    }

    /**
     * Saves the current app version in the database.
     */
    private function saveCurrentVersion()
    {
        $version = SolidInvoiceCoreBundle::VERSION;

        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository(Version::class);

        $repository->updateVersion($version);
    }

    /**
     * @param array $data
     *
     * @throws EnvironmentIsBrokenException|InvalidArgumentException|DependencyInjectionException\ServiceCircularReferenceException|DependencyInjectionException\ServiceNotFoundException
     */
    protected function saveConfig(array $data)
    {
        $time = new DateTime('NOW');

        $config = [
            'locale' => $data['locale'],
            'base_url' => $data['base_url'],
            'installed' => $time->format(DateTime::ISO8601),
            'secret' => Key::createNewRandomKey()->saveToAsciiSafeString(),
        ];

        $this->get('solidinvoice.core.config_writer')->dump($config);

        $countryCode = explode('_', $data['locale'])[1] ?? $data['locale'];

        $vatCalculator = $this->get(VatCalculator::class);
        if ($vatCalculator->shouldCollectVAT($countryCode)) {
            $rate = $vatCalculator->getTaxRateForCountry($countryCode);

            $tax = new Tax();
            $tax->setRate($rate * 100)
                ->setType(Tax::TYPE_INCLUSIVE)
                ->setName('VAT');

            $em = $this->get('doctrine')->getManager();
            $em->persist($tax);
            $em->flush();
        }

        $this->get('settings')->set(CurrencyFactory::CURRENCY_PATH, $data['currency'] ?? CurrencyFactory::DEFAULT_CURRENCY);
    }
}
