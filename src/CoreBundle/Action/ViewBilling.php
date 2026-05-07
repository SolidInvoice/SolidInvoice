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
use Mpdf\MpdfException;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ViewBilling
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RouterInterface $router,
        private readonly CompanySelector $companySelector,
        private readonly Generator $pdfGenerator,
        private readonly Environment $twig,
        private readonly EmailVerificationGateInterface $emailVerificationGate,
    ) {
    }

    /**
     * View a quote if not logged in.
     *
     * @return array{quote: Quote, title: string, template: string}|Response
     * @throws InvalidArgumentException|InvalidParameterException|MissingMandatoryParametersException|NotFoundHttpException|RouteNotFoundException|LoaderError|MpdfException|RuntimeError|SyntaxError
     */
    #[Template('@SolidInvoiceCore/View/quote.html.twig')]
    public function quoteAction(Request $request, string $uuid): array|Response
    {
        $options = [
            'repository' => Quote::class,
            'route' => '_quotes_view',
            'template' => '@SolidInvoiceQuote/quote_template.html.twig',
            'uuid' => $uuid,
            'entity' => 'quote',
            'pdfTemplate' => '@SolidInvoiceQuote/Pdf/quote.html.twig',
        ];

        return $this->createResponse($request, $options);
    }

    /**
     * View a invoice if not logged in.
     *
     * @return array{invoice: Invoice, title: string, template: string}|Response
     * @throws InvalidArgumentException|InvalidParameterException|MissingMandatoryParametersException|NotFoundHttpException|RouteNotFoundException|LoaderError|MpdfException|RuntimeError|SyntaxError
     */
    #[Template('@SolidInvoiceCore/View/invoice.html.twig')]
    public function invoiceAction(Request $request, string $uuid): array|Response
    {
        $options = [
            'repository' => Invoice::class,
            'route' => '_invoices_view',
            'template' => '@SolidInvoiceInvoice/external_invoice_view.html.twig',
            'uuid' => $uuid,
            'entity' => 'invoice',
            'pdfTemplate' => '@SolidInvoiceInvoice/Pdf/invoice.html.twig',
        ];

        return $this->createResponse($request, $options);
    }

    /**
     * @param array{"repository": class-string, "route": string, "template": string, "uuid": string, "entity": string, "pdfTemplate": string} $options
     * @return array<string, mixed>|Response
     * @throws NotFoundHttpException|InvalidArgumentException|InvalidParameterException|MissingMandatoryParametersException|RouteNotFoundException|LoaderError|MpdfException|RuntimeError|SyntaxError
     */
    private function createResponse(Request $request, array $options): array|Response
    {
        $repository = $this->registry->getRepository($options['repository']);

        $entity = $repository->findOneBy(['uuid' => $options['uuid']]);

        if (null === $entity) {
            throw new NotFoundHttpException(sprintf('"%s" with id %s does not exist', ucfirst((string) $options['entity']), $options['uuid']));
        }

        if ($this->emailVerificationGate->isCompanyGated($entity->getCompany())) {
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

        $this->companySelector->switchCompany($entity->getCompany()->getId());

        // Handle PDF format
        if ('pdf' === $request->getRequestFormat() && $this->pdfGenerator->canPrintPdf()) {
            $html = $this->twig->render($options['pdfTemplate'], [$options['entity'] => $entity]);
            $filename = sprintf('%s_%s.pdf', $options['entity'], $entityId);

            return new PdfResponse($this->pdfGenerator->generate($html), $filename);
        }

        return [
            $options['entity'] => $entity,
            'title' => $options['entity'] . ' #' . $entityId,
            'template' => $options['template'],
        ];
    }
}
