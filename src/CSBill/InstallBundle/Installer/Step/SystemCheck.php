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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use CSBill\InstallBundle\Installer\AbstractStep;
use CSBill\InstallBundle\Installer\StepViewInterface;

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
            'errors' => $this->errors
        );
    }

    /**
     * @return array
     * @throws \RuntimeException
     */
    public function getSystemChecks()
    {
        $rootDir = $this->get('kernel')->getRootDir();

        $process = new Process(sprintf('php %s/check.php', $rootDir));
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $check = $process->getOutput();

        $output = explode("\n", $check);

        $recommended = $this->getOutput($output, 'mandatory requirements');
        $optional = $this->getOutput($output, 'optional recommendations');

        return array('recommended' => $recommended, 'optional' => $optional);
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

    /**
     * Parses through the output of the system check, and extracts the requirements
     *
     * @param  array                      $output The ouput generated from the system check
     * @param  string                     $header the header to look for to get the requirements
     * @return array<string,string|array>
     */
    public function getOutput($output = array(), $header = '')
    {
        reset($output);

        $heading = null;
        $content = null;

        while (($line = next($output)) !== false) {
            if (strpos(strtolower($line), strtolower($header)) !== false) {
                $content = array();
                $heading = trim(str_replace('**', '', $line));

                do {
                    $line = next($output);
                } while (substr($line, 0, 1) === '*');

                $line = next($output);

                do {
                    if ($line !== '') {
                        $content[] = $line;
                    }

                    $line = next($output);
                } while (substr(strtolower($line), 0, 2) !== '**' && false !== $line);
            }
        }

        return array('heading' => $heading, 'values' => $content);
    }
}
