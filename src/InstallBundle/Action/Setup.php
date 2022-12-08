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

use DateTime;
use DateTimeInterface;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Doctrine\Persistence\ManagerRegistry;
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
use Symfony\Component\DependencyInjection\Exception as DependencyInjectionException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

final class Setup
{
    private PasswordHasherFactoryInterface $passwordHasherFactory;

    private FormFactoryInterface $formFactory;

    private ManagerRegistry $doctrine;

    private ConfigWriter $configWriter;

    private VatCalculator $vatCalculator;

    private SystemConfig $systemConfig;

    private RouterInterface $router;

    public function __construct(
        PasswordHasherFactoryInterface $passwordHasherFactory,
        FormFactoryInterface $formFactory,
        ManagerRegistry $doctrine,
        ConfigWriter $configWriter,
        VatCalculator $vatCalculator,
        SystemConfig $systemConfig,
        RouterInterface $router
    ) {
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->formFactory = $formFactory;
        $this->doctrine = $doctrine;
        $this->configWriter = $configWriter;
        $this->vatCalculator = $vatCalculator;
        $this->systemConfig = $systemConfig;
        $this->router = $router;
    }

    /**
     * @return Template|RedirectResponse
     * @throws EnvironmentIsBrokenException
     */
    public function __invoke(Request $request)
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->handleForm($request);
        }

        return $this->render($this->getForm());
    }

    private function getForm(): FormInterface
    {
        $config = $this->configWriter->getConfigValues();

        $data = [
            'locale' => $config['locale'] ?? null,
            'currency' => $this->systemConfig->get(CurrencyFactory::CURRENCY_PATH),
        ];

        return $this->formFactory->create(SystemInformationForm::class, $data, ['userCount' => $this->getUserCount()]);
    }

    private function getUserCount(): int
    {
        static $userCount;

        if (null === $userCount) {
            $userCount = $this->doctrine->getRepository(User::class)->getUserCount();
        }

        return $userCount;
    }

    /**
     * @return Template|RedirectResponse
     * @throws EnvironmentIsBrokenException
     */
    public function handleForm(Request $request)
    {
        $form = $this->getForm();

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

    /**
     * @param array{username: string, password: string, email_address: string} $data
     */
    private function createAdminUser(array $data): void
    {
        $user = new User();

        $encoder = $this->passwordHasherFactory->getPasswordHasher($user);

        $password = $encoder->hash($data['password']);

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
    private function saveCurrentVersion(): void
    {
        $version = SolidInvoiceCoreBundle::VERSION;

        $entityManager = $this->doctrine->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository(Version::class);

        $repository->updateVersion($version);
    }

    /**
     * @param array{locale: string, currency?: string} $data
     *
     * @throws EnvironmentIsBrokenException|InvalidArgumentException|DependencyInjectionException\ServiceCircularReferenceException|DependencyInjectionException\ServiceNotFoundException
     */
    private function saveConfig(array $data): void
    {
        $time = new DateTime('NOW');

        $config = [
            'locale' => $data['locale'],
            'installed' => $time->format(DateTimeInterface::ATOM),
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

    private function render(FormInterface $form): Template
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
