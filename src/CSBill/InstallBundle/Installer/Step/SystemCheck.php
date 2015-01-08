<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer\Step;

use CSBill\InstallBundle\Installer\AbstractStep;
use CSBill\InstallBundle\Installer\StepViewInterface;
use Symfony\Component\HttpFoundation\Request;

class SystemCheck extends AbstractStep implements StepViewInterface
{
    /**
     * @var array
     */
    protected static $checks;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var Request
     */
    protected $request;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (null === self::$checks) {
            self::$checks = $this->getSystemChecks();
        }
    }

    /**
     * The view to render for this installation step
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'CSBillInstallBundle:Install:system_check.html.twig';
    }

    /**
     * @return array
     * @throws \RuntimeException
     */
    public function getViewVars()
    {
        return array(
            'checks' => self::$checks,
            'errors' => $this->errors,
        );
    }

    /**
     * @return array
     * @throws \RuntimeException
     */
    public function getSystemChecks()
    {
        $rootDir = $this->get('kernel')->getRootDir();

        require_once $rootDir.DIRECTORY_SEPARATOR.'SymfonyRequirements.php';

        $symfonyRequirements = new \SymfonyRequirements();

        $recommended = array();
        foreach ($symfonyRequirements->getRequirements() as $req) {
            $recommended[] = $this->getRequirement($req);
        }

        $optional = array();
        foreach ($symfonyRequirements->getRecommendations() as $req) {
            $optional[] = $this->getRequirement($req);
        }

        return [
            'recommended' => [
                'heading' => 'mandatory requirements',
                'values' => $recommended,
            ],
            'optional' => [
                'heading' => 'optional recommendations',
                'values' => $optional,
            ]
        ];
    }

    /**
     * @param \Requirement $requirement
     *
     * @return string
     */
    private function getRequirement(\Requirement $requirement)
    {
        $string = '';
        $string .= $requirement->isFulfilled() ? 'OK' : ($requirement->isOptional() ? 'WARNING' : 'ERROR');
        $string .= ' '.$requirement->getTestMessage();

        if (!$requirement->isFulfilled()) {
            $string .= sprintf("          %s", $requirement->getHelpText());
        }

        return $string;
    }

    /**
     * Validates that the system meets the minimum requirements
     *
     * @return boolean
     */
    public function isValid()
    {
        if ($this->request->isMethod('POST')) {
            foreach (self::$checks['recommended']['values'] as $value) {
                if (substr(trim($value), 0, 2) !== 'OK') {
                    $value = str_replace(array('ERROR', 'WARNING'), '', $value);
                    $this->errors[] = $value;
                }
            }

            return count($this->errors) === 0;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        // noop
    }

    /**
     * {@inheritDoc}
     */
    public function handleRequest(Request $request)
    {
        $this->request = $request;
    }
}
