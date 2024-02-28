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

namespace SolidInvoice\CoreBundle\Form\Handler;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Form\Type\CompanyType;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use function assert;

final class CompanyFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface
{
    use SaveableTrait;

    private CompanySelector $companySelector;

    private RouterInterface $router;

    private Security $security;

    public function __construct(
        Security $security,
        CompanySelector $companySelector,
        RouterInterface $router
    ) {
        $this->security = $security;
        $this->companySelector = $companySelector;
        $this->router = $router;
    }

    public function getForm(FormFactoryInterface $factory, Options $options): string
    {
        return CompanyType::class;
    }

    public function getResponse(FormRequest $formRequest): Template
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        return new Template(
            '@SolidInvoiceCore/Company/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
                'allowCancel' => ! $user->getCompanies()->isEmpty(),
            ]
        );
    }

    public function onSuccess(FormRequest $form, $data): ?Response
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        $company = new Company();
        $company->setName($data['name']);
        $company->addUser($user);
        $company->defaultCurrency = $data['currency'];

        $this->save($company);
        $request = $form->getRequest();
        assert($request instanceof Request);
        $request->getSession()->set('company', $company->getId());

        $this->companySelector->switchCompany($company->getId());

        return new RedirectResponse($this->router->generate('_dashboard'));
    }
}
