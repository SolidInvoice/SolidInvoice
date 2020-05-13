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

namespace SolidInvoice\InstallBundle\Action;

use DateTime;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use InvalidArgumentException;
use Mpociot\VatCalculator\VatCalculator;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InstallBundle\Form\Step\SystemInformationForm;
use SolidInvoice\MoneyBundle\Factory\CurrencyFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\Exception as DependencyInjectionException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class Setup
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var VatCalculator
     */
    private $vatCalculator;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        EncoderFactoryInterface $encoderFactory,
        FormFactoryInterface $formFactory,
        RegistryInterface $doctrine,
        ConfigWriter $configWriter,
        VatCalculator $vatCalculator,
        SystemConfig $systemConfig,
        RouterInterface $router
    ) {
        $this->encoderFactory = $encoderFactory;
        $this->formFactory = $formFactory;
        $this->doctrine = $doctrine;
        $this->configWriter = $configWriter;
        $this->vatCalculator = $vatCalculator;
        $this->systemConfig = $systemConfig;
        $this->router = $router;
    }

    public function __invoke(Request $request)
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->handleForm($request);
        }

        return $this->render($this->getForm($request));
    }

    private function getForm(Request $request): FormInterface
    {
        return $this->formFactory->create(SystemInformationForm::class, [], ['userCount' => $this->getUserCount()]);
    }

    /**
     * @return int
     */
    private function getUserCount()
    {
        static $userCount;

        if (null === $userCount) {
            $userCount = $this->doctrine->getRepository(User::class)->getUserCount();
        }

        return $userCount;
    }

    public function handleForm(Request $request)
    {
        $form = $this->getForm($request);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            if (0 === $this->getUserCount()) {
                $this->createAdminUser($data);
            }

            $this->saveCurrentVersion();
            $this->saveConfig($data);

            $request->getSession()->set('installation_step', true);

            return new RedirectResponse($this->router->generate('_install_finish'));
        }

        return $this->render($form);
    }

    private function createAdminUser(array $data)
    {
        $user = new User();

        $encoder = $this->encoderFactory->getEncoder($user);

        $password = $encoder->encodePassword($data['password'], null);

        $user->setUsername($data['username'])
            ->setEmail($data['email_address'])
            ->setPassword($password)
            ->setEnabled(true);

        $entityManager = $this->doctrine->getManager();

        $entityManager->persist($user);
        $entityManager->flush();
    }

    /**
     * Saves the current app version in the database.
     */
    private function saveCurrentVersion()
    {
        $version = SolidInvoiceCoreBundle::VERSION;

        $entityManager = $this->doctrine->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository(Version::class);

        $repository->updateVersion($version);
    }

    /**
     * @throws EnvironmentIsBrokenException|InvalidArgumentException|DependencyInjectionException\ServiceCircularReferenceException|DependencyInjectionException\ServiceNotFoundException
     */
    protected function saveConfig(array $data)
    {
        $time = new DateTime('NOW');

        $config = [
            'locale' => $data['locale'],
            'installed' => $time->format(DateTime::ISO8601),
            'secret' => Key::createNewRandomKey()->saveToAsciiSafeString(),
        ];

        $this->configWriter->dump($config);

        $countryCode = explode('_', $data['locale'])[1] ?? $data['locale'];

        if ($this->vatCalculator->shouldCollectVAT($countryCode)) {
            $rate = $this->vatCalculator->getTaxRateForCountry($countryCode);

            $tax = new Tax();
            $tax->setRate($rate * 100)
                ->setType(Tax::TYPE_INCLUSIVE)
                ->setName('VAT');

            $em = $this->doctrine->getManager();
            $em->persist($tax);
            $em->flush();
        }

        $this->systemConfig->set(CurrencyFactory::CURRENCY_PATH, $data['currency'] ?? CurrencyFactory::DEFAULT_CURRENCY);
    }

    protected function render(FormInterface $form): Template
    {
        return new Template(
            '@SolidInvoiceInstall/setup.html.twig',
            [
                'form' => $form->createView(),
                'userCount' => $this->getUserCount(),
            ]
        );
    }
}
