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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

final class DeleteAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(Request $request, string $id): Response
    {
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            throw $this->createAccessDeniedException('Custom fields are not available on the current plan.');
        }

        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('cf_delete_' . $id, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $field = $this->em->find(CustomField::class, Ulid::fromString($id));
        if ($field === null) {
            throw new NotFoundHttpException('Field not found.');
        }

        $this->em->remove($field);
        $this->em->flush();

        $this->addFlash('success', 'Custom field deleted.');
        return new RedirectResponse($this->generateUrl('_settings_custom_fields'));
    }
}
