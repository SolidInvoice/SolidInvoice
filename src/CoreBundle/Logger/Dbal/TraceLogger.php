<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Logger\Dbal;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Includes backtrace and executed SQLs in a Debug Stack.
 */
class TraceLogger implements SQLLogger
{
    /**
     * Executed SQL queries.
     *
     * @var array
     */
    public $queries = [];

    /**
     * If the logger is enabled (log queries) or not.
     *
     * @var bool
     */
    public $enabled = true;

    /**
     * @var float|null
     */
    public $start = null;

    /**
     * @var int
     */
    public $currentQuery = 0;

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        if ($this->enabled) {
            $backtrace = $this->getBactrace();

            $this->start = microtime(true);
            $this->queries[++$this->currentQuery] = ['sql' => $sql, 'params' => $params, 'types' => $types, 'executionMS' => 0, 'trace' => $backtrace];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        if ($this->enabled) {
            $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
        }
    }

    private function getBactrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($backtrace as $key => $debug) {
            if (!$this->isInternalClass($debug['class'])) {
                $trace = array_slice($backtrace, $key - 1, 10);

                return $this->formatTrace($trace);
            }
        }

        return [];
    }

    private function formatTrace(array $trace)
    {
        $backtrace = [];

        foreach ($trace as $index => $line) {
            $backtrace[$index] = '';

            if (isset($trace[$index + 1]['class'])) {
                $backtrace[$index] .= $trace[$index + 1]['class'];
            } else {
                $backtrace[$index] .= isset($line['object']) ? get_class($line['object']) : $line['function'];
            }

            $backtrace[$index] .= '::';

            if (isset($trace[$index + 1])) {
                $backtrace[$index] .= $trace[$index + 1]['function'];
            } else {
                $backtrace[$index] .= $line['function'];
            }

            if (isset($line['line'])) {
                $backtrace[$index] .= ' (L'.$line['line'].')';
            }
        }

        return $backtrace;
    }

    private function isInternalClass(&$class)
    {
        return substr($class, 0, strpos($class, '\\')) === 'Doctrine' || $class === __CLASS__;
    }
}
