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
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

final class EditAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(string $id): Response
    {
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return $this->render('@SolidInvoiceSettings/CustomField/gated.html.twig');
        }

        $field = $this->em->find(CustomField::class, Ulid::fromString($id));
        if ($field === null) {
            throw new NotFoundHttpException('Field not found.');
        }

        return $this->render('@SolidInvoiceSettings/CustomField/edit.html.twig', [
            'field' => $field,
            'mode' => 'edit',
        ]);
    }
}
