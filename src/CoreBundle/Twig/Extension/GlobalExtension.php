<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Twig\Extension;

use Carbon\Carbon;
use CSBill\CoreBundle\CSBillCoreBundle;
use CSBill\SettingsBundle\Exception\InvalidSettingException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Money\Money;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class GlobalExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const DEFAULT_LOGO = 'img/logo.png';

    /**
     * Get global twig variables.
     *
     * @return array
     */
    public function getGlobals(): array
    {
        $globals = [
            'query' => $this->getQuery(),
            'app_version' => CSBillCoreBundle::VERSION,
            'app_name' => CSBillCoreBundle::APP_NAME,
        ];

        if ($this->container->getParameter('installed')) {
            $globals['app_name'] = $this->container->get('settings')->get('system/company/company_name');
        }

        return $globals;
    }

    /**
     * Get the url query.
     *
     * @return array
     */
    protected function getQuery(): array
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if (!$request) {
            return [];
        }

        $params = array_merge($request->query->all(), $request->attributes->all());

        foreach (array_keys($params) as $key) {
            if (substr($key, 0, 1) == '_') {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('percentage', [$this, 'formatPercentage']),
            new \Twig_SimpleFilter('diff', [$this, 'dateDiff']),
            new \Twig_SimpleFilter('md5', 'md5'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_Function('icon', [$this, 'displayIcon'], ['is_safe' => ['html']]),
            new \Twig_Function('app_logo', [$this, 'displayAppLogo'], ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    public function displayAppLogo(\Twig_Environment $env): string
    {
        $config = $this->container->get('settings');
        $logo = self::DEFAULT_LOGO;

        if ($this->container->getParameter('installed')) {
            try {
                $logo = $config->get('system/company/logo');

                if (null !== $logo) {
                    $logo = 'uploads/'.$logo;
                }
            } catch (InvalidSettingException | TableNotFoundException $e) {
            } finally {
                if (null === $logo) {
                    $logo = self::DEFAULT_LOGO;
                }
            }
        }

        return $env->createTemplate('<img src="{{ asset(logo) }}" width="25" style="display: inline"/>')->render(['logo' => $logo]);
    }

    /**
     * Displays an icon.
     *
     * @param string $iconName
     * @param array  $options
     *
     * @return string
     */
    public function displayIcon(string $iconName, array $options = []): string
    {
        $options = implode(' ', $options);
        $class = sprintf('fa fa-%s', $iconName);

        if (!empty($options)) {
            $class .= ' '.$options;
        }

        return sprintf('<i class="%s"></i>', $class);
    }

    /**
     * @param int|float|Money $amount
     * @param float           $percentage
     *
     * @return float|int
     */
    public function formatPercentage($amount, float $percentage = 0)
    {
        if ($percentage > 0) {
            $percentage /= 100;
        }

        if ($amount instanceof Money) {
            return $amount->multiply($percentage)->getAmount();
        }

        return $amount * $percentage;
    }

    /**
     * Returns a human-readible diff for dates.
     *
     * @param \DateTime $date
     *
     * @return string
     */
    public function dateDiff(\DateTime $date): string
    {
        $carbon = Carbon::instance($date);

        return $carbon->diffForHumans();
    }
}
