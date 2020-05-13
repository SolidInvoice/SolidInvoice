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

use InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
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
     * @return Template|Response
     *
     * @throws InvalidArgumentException|InvalidParameterException|InvalidUuidStringException|MissingMandatoryParametersException|NotFoundHttpException|RouteNotFoundException
     */
    public function quoteAction(string $uuid)
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
     * @return Response|Template
     *
     * @throws InvalidArgumentException|InvalidParameterException|InvalidUuidStringException|MissingMandatoryParametersException|NotFoundHttpException|RouteNotFoundException
     */
    public function invoiceAction(string $uuid)
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
     * @return Template|Response
     *
     * @throws NotFoundHttpException|InvalidArgumentException|InvalidUuidStringException|InvalidParameterException|MissingMandatoryParametersException|RouteNotFoundException
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

        return new Template(
            '@SolidInvoiceCore/View/'.$options['entity'].'.html.twig',
            [
                $options['entity'] => $entity,
                'title' => $options['entity'].' #'.$entity->getId(),
                'template' => $options['template'],
            ]
        );
    }
}
