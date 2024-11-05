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

namespace SolidInvoice\CoreBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Uid\Ulid;

class ViewBilling
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RouterInterface $router
    ) {
    }

    /**
     * View a quote if not logged in.
     *
     * @throws InvalidArgumentException|InvalidParameterException|MissingMandatoryParametersException|NotFoundHttpException|RouteNotFoundException
     */
    public function quoteAction(string $uuid): Template|Response
    {
        $options = [
            'repository' => Quote::class,
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
     * @throws InvalidArgumentException|InvalidParameterException|MissingMandatoryParametersException|NotFoundHttpException|RouteNotFoundException
     */
    public function invoiceAction(string $uuid): Response|Template
    {
        $options = [
            'repository' => Invoice::class,
            'route' => '_invoices_view',
            'template' => '@SolidInvoiceInvoice/invoice_template.html.twig',
            'uuid' => $uuid,
            'entity' => 'invoice',
        ];

        return $this->createResponse($options);
    }

    /**
     * @throws NotFoundHttpException|InvalidArgumentException|InvalidParameterException|MissingMandatoryParametersException|RouteNotFoundException
     */
    private function createResponse(array $options): Template|Response
    {
        $repository = $this->registry->getRepository($options['repository']);

        $entity = $repository->findOneBy(['uuid' => Ulid::fromString($options['uuid'])]);

        if (null === $entity) {
            throw new NotFoundHttpException(sprintf('"%s" with id %s does not exist', ucfirst((string) $options['entity']), $options['uuid']));
        }

        try {
            if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                return new RedirectResponse($this->router->generate($options['route'], ['id' => $entity->getId()]));
            }
        } catch (AuthenticationCredentialsNotFoundException) {
        }

        $entityId = null;

        if ($entity instanceof Invoice) {
            $entityId = $entity->getInvoiceId();
        } elseif ($entity instanceof Quote) {
            $entityId = $entity->getQuoteId();
        }

        return new Template(
            '@SolidInvoiceCore/View/' . $options['entity'] . '.html.twig',
            [
                $options['entity'] => $entity,
                'title' => $options['entity'] . ' #' . $entityId,
                'template' => $options['template'],
            ]
        );
    }
}
