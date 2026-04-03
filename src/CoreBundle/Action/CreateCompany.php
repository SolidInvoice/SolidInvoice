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

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Form\Type\CompanyType;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepository;
use SolidWorx\Platform\SaasBundle\Trial\TrialManagerInterface;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use function assert;

final class CreateCompany extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly CompanyRepository $companyRepository,
        private readonly RouterInterface $router,
        private readonly ToggleInterface $toggler,
        private readonly ?TrialManagerInterface $trialManager = null,
        private readonly ?PlanRepository $planRepository = null,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        $form = $this->createForm(CompanyType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company = $form->getData();
            assert($company instanceof Company);

            $company->addUser($user);
            // @TODO: Set the user as the owner of the company

            $this->companyRepository->save($company);

            $request->getSession()->set('company', $company->getId());

            return new RedirectResponse($this->router->generate('_dashboard'));
        }

        $planPrice = null;
        $userHasTrial = false;
        $planHasTrial = false;
        $trialDays = null;

        if ($this->toggler->isActive('saas_enabled')) {
            $userHasTrial = $this->trialManager?->userHasTrial($user);
            $plan = $this->planRepository?->findOneBy([]);
            if ($plan instanceof Plan) {
                $formatter = new IntlMoneyFormatter(
                    new \NumberFormatter('en_US', \NumberFormatter::CURRENCY),
                    new ISOCurrencies()
                );
                $planPrice = $formatter->format(Money::USD($plan->getPrice()));

                $trialDuration = $plan->getTrialDuration();
                if ($trialDuration !== null) {
                    $planHasTrial = true;
                    // Create a reference date and add the interval to compute the total days
                    $reference = new \DateTimeImmutable();
                    $trialDays = $reference->diff($reference->add($trialDuration))->days;
                }
            }
        }

        return $this->render(
            '@SolidInvoiceCore/Company/create.html.twig',
            [
                'form' => $form,
                'allowCancel' => ! $user->getCompanies()->isEmpty(),
                'planPrice' => $planPrice,
                'userHasTrial' => $userHasTrial,
                'planHasTrial' => $planHasTrial,
                'trialDays' => $trialDays,
            ]
        );
    }
}
