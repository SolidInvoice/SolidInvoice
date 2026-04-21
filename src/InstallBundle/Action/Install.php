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

use const JSON_THROW_ON_ERROR;
use DateTimeInterface;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Form\Type\InstallationType;
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\HttpFoundation\EventStreamResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;
use function date;
use function json_encode;
use function Symfony\Component\String\u;

final class Install extends AbstractController
{
    /**
     * @param ServiceLocator<InstallationStepInterface> $steps
     */
    public function __construct(
        #[AutowireLocator(services: InstallationStepInterface::DI_TAG, defaultIndexMethod: 'getLabel', defaultPriorityMethod: 'priority')]
        private readonly ServiceLocator $steps,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ConfigWriter $configWriter,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        private readonly ?string $installed,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($this->installed) {
            throw $this->createNotFoundException();
        }

        /** @var FormFlowInterface $form */
        $form = $this->createForm(InstallationType::class, new Installation())
            ->handleRequest($request);

        if ($request->query->has('action') && $request->query->has('token')) {
            return $this->handleInstallationStep($form, $request);
        }

        if ($form->isSubmitted() && $form->isValid() && $form->isFinished()) {
            /** @var Installation $formData */
            $formData = $form->getData();

            $this->configWriter->save([
                'installed' => date(DateTimeInterface::ATOM),
                'locale' => $formData->userAccount->locale,
                'installation_id' => Uuid::v4()->toString(),
                'application_url' => (string) $formData->applicationUrl,
            ]);

            $form->reset();

            $user = $this->userRepository->findOneBy(['email' => $formData->userAccount->emailAddress]);
            if ($user instanceof User) {
                return $this->security->login($user, authenticatorName: 'security.authenticator.form_login.main', firewallName: 'main');
            }

            return $this->redirectToRoute('_login_main');
        }

        return $this->render('@SolidInvoiceInstall/install.html.twig', [
            'form' => $form->getStepForm(),
        ]);
    }

    private function handleInstallationStep(FormFlowInterface $form, Request $request): EventStreamResponse
    {
        /** @var Installation $data */
        $data = $form->getData();

        if (! $this->csrfTokenManager->isTokenValid(
            new CsrfToken('system_installation', $request->query->get('token')),
        )) {
            throw new BadRequestHttpException();
        }

        $action = u($request->query->get('action'))->replace('_', ' ')->title()->toString();

        if (! $this->steps->has($action)) {
            throw new BadRequestException('Invalid action: ' . $action);
        }

        $step = $this->steps->get($action);

        return new EventStreamResponse(function () use ($data, $step): \Generator {
            try {
                yield from $step->execute($data, function (string $content): \Generator {
                    yield new ServerEvent($content);
                });

                yield new ServerEvent(json_encode(['status' => 'success'], JSON_THROW_ON_ERROR), 'complete');
            } catch (Throwable $e) {
                // Send error event with details
                yield new ServerEvent(
                    json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ], JSON_THROW_ON_ERROR),
                    'error'
                );
            }
        });
    }
}
