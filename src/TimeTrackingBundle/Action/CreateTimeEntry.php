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

namespace SolidInvoice\TimeTrackingBundle\Action;

use SolidInvoice\TimeTrackingBundle\Entity\TimeEntry;
use SolidInvoice\TimeTrackingBundle\Form\Type\TimeEntryType;
use SolidInvoice\TimeTrackingBundle\Manager\TimeEntryManager;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use function assert;

final class CreateTimeEntry extends AbstractController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly TimeEntryManager $timeEntryManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $timeEntry = new TimeEntry();
        $form = $this->createForm(TimeEntryType::class, $timeEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client = $timeEntry->getClient();
            assert($client !== null);

            $this->timeEntryManager->createManualEntry(
                $user,
                $client,
                $timeEntry->getDuration(),
                $timeEntry->getDate(),
                $timeEntry->getDescription(),
            );

            $this->addFlash('success', 'Time entry created successfully.');

            return new RedirectResponse($this->router->generate('_time_tracking_index'));
        }

        return $this->render('@SolidInvoiceTimeTracking/Default/create.html.twig', [
            'form' => $form,
            'time_entry' => $timeEntry,
        ]);
    }
}
