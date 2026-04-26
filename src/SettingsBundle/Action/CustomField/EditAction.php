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

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\SettingsBundle\Form\Type\CustomFieldDefinitionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

final class EditAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomFieldValueRepository $values,
    ) {
    }

    public function __invoke(Request $request, string $id): Response
    {
        $field = $this->em->find(CustomField::class, Ulid::fromString($id));
        if ($field === null) {
            throw new NotFoundHttpException('Field not found.');
        }

        $usageCount = $this->values->countByField($field);

        $form = $this->createForm(CustomFieldDefinitionType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Custom field updated.');
            return new RedirectResponse($this->generateUrl('_settings_custom_fields'));
        }

        return $this->render('@SolidInvoiceSettings/CustomField/edit.html.twig', [
            'form' => $form->createView(),
            'mode' => 'edit',
            'field' => $field,
            'usageCount' => $usageCount,
        ]);
    }
}
