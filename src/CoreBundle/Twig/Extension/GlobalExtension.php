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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use Carbon\Carbon;
use DateTime;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function implode;

class GlobalExtension extends AbstractExtension implements GlobalsInterface
{
    private const DEFAULT_LOGO = 'png|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';

    public function __construct(
        private readonly Calculator $calculator,
        private readonly Generator $pdfGenerator,
        private readonly SystemConfig $systemConfig,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CompanySelector $companySelector,
        private readonly ?string $installed
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException|ServiceCircularReferenceException|ServiceNotFoundException
     */
    public function getGlobals(): array
    {
        return [
            'query' => $this->getQuery(),
            'app_version' => SolidInvoiceCoreBundle::VERSION,
            'app_name' => SolidInvoiceCoreBundle::APP_NAME,
        ];
    }

    /**
     * @return array<string, string>
     *
     * @throws ServiceCircularReferenceException|ServiceNotFoundException
     */
    protected function getQuery(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            return [];
        }

        $params = array_merge($request->query->all(), $request->attributes->all());

        foreach (array_keys($params) as $key) {
            if (str_starts_with($key, '_')) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('percentage', $this->calculator->calculatePercentage(...)),

            new TwigFilter('diff', $this->dateDiff(...)),

            new TwigFilter('md5', 'md5'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('icon', $this->displayIcon(...), ['is_safe' => ['html']]),

            new TwigFunction('app_logo', $this->displayAppLogo(...), ['is_safe' => ['html'], 'needs_environment' => true]),

            new TwigFunction('company_name', function (): string {
                if ($this->security->getUser() instanceof UserInterface) {
                    return $this->systemConfig->get('system/company/company_name') ?? SolidInvoiceCoreBundle::APP_NAME;
                }

                return SolidInvoiceCoreBundle::APP_NAME;
            }),

            new TwigFunction('company_id', $this->companySelector->getCompany(...)),

            new TwigFunction('can_print_pdf', $this->pdfGenerator->canPrintPdf(...)),
        ];
    }

    /**
     * @throws InvalidArgumentException|ServiceCircularReferenceException|ServiceNotFoundException|LoaderError|SyntaxError
     */
    public function displayAppLogo(Environment $env, string $width = 'auto', bool $showDefault = true): string
    {
        $logo = $showDefault ? self::DEFAULT_LOGO : null;

        if ($this->installed) {
            $logo = $this->systemConfig->get('system/company/logo');

            if (null === $logo) {
                $logo = $showDefault ? self::DEFAULT_LOGO : null;
            }
        }

        if (null === $logo) {
            return '';
        }

        [$type, $logo] = explode('|', $logo);

        return $env->createTemplate('<img src="data:image/{{ type }};base64,{{ logo }}" class="brand-image" width="' . $width . '"/>')->render(['type' => $type, 'logo' => $logo]);
    }

    /**
     * @param list<string> $options
     */
    public function displayIcon(string $iconName, array $options = []): string
    {
        $class = sprintf('fa fa-%s', $iconName);

        if ([] !== $options) {
            $class .= ' ' . implode(' ', $options);
        }

        return sprintf('<i class="%s"></i>', $class);
    }

    /**
     * Returns a human-readable diff for dates.
     */
    public function dateDiff(DateTime $date): string
    {
        return Carbon::instance($date)->diffForHumans();
    }
}
