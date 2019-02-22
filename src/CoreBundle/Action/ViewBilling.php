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

namespace SolidInvoice\CoreBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ViewBilling
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RegistryInterface $registry, AuthorizationCheckerInterface $authorizationChecker, RouterInterface $router)
    {
        $this->registry = $registry;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
    }

    /**
     * View a quote if not logged in.
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function quoteAction(string $uuid): Response
    {
        $options = [
            'repository' => 'SolidInvoiceQuoteBundle:Quote',
            'route' => '_quotes_view',
            'template' => '@SolidInvoiceQuote/quote_template.html.twig',
            'uuid' => $uuid,
            'entity' => 'quote',
        ];

        return $this->createResponse($options);
    }

    /**
     * View a invoice if not logged in.
     *
     * @param string $uuid
     *
     * @return Response|Template
     */
    public function invoiceAction(string $uuid)
    {
        $options = [
            'repository' => 'SolidInvoiceInvoiceBundle:Invoice',
            'route' => '_invoices_view',
            'template' => '@SolidInvoiceInvoice/invoice_template.html.twig',
            'uuid' => $uuid,
            'entity' => 'invoice',
        ];

        return $this->createResponse($options);
    }

    /**
     * @param array $options
     *
     * @return Template|Response
     *
     * @throws NotFoundHttpException
     */
    private function createResponse(array $options)
    {
        $repository = $this->registry->getRepository($options['repository']);

        $entity = $repository->findOneBy(['uuid' => Uuid::fromString($options['uuid'])]);

        if (null === $entity) {
            throw new NotFoundHttpException(sprintf('"%s" with id %s does not exist', ucfirst($options['entity']), $options['uuid']));
        }

        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return new RedirectResponse($this->router->generate($options['route'], ['id' => $entity->getId()]));
        }

        $template = '@SolidInvoiceCore/View/'.$options['entity'].'.html.twig';

        return new Template(
            $template,
            [
                $options['entity'] => $entity,
                'title' => $options['entity'].' #'.$entity->getId(),
                'template' => $options['template'],
            ]
        );
    }
}
