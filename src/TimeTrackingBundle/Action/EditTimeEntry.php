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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\TimeTrackingBundle\Entity\TimeEntry;
use SolidInvoice\TimeTrackingBundle\Form\Type\TimeEntryType;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;

final class EditTimeEntry extends AbstractController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function __invoke(Request $request, Ulid $id): Response
    {
        $em = $this->doctrine->getManager();
        $timeEntry = $em->find(TimeEntry::class, $id);

        if (! $timeEntry instanceof TimeEntry) {
            throw $this->createNotFoundException('Time entry not found.');
        }

        $user = $this->getUser();
        if (! $user instanceof User || $timeEntry->getUser() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to edit this time entry.');
        }

        if ($timeEntry->isLocked()) {
            $this->addFlash('warning', 'This time entry is locked because it has been invoiced.');

            return new RedirectResponse($this->router->generate('_time_tracking_index'));
        }

        $form = $this->createForm(TimeEntryType::class, $timeEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Time entry updated successfully.');

            return new RedirectResponse($this->router->generate('_time_tracking_index'));
        }

        return $this->render('@SolidInvoiceTimeTracking/Default/edit.html.twig', [
            'form' => $form,
            'time_entry' => $timeEntry,
        ]);
    }
}
