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

namespace SolidInvoice\CoreBundle\Logger\Dbal;

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
            if (!$this->isInternalClass($debug['class'] ?? null)) {
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
                $backtrace[$index] .= ' (L' . $line['line'] . ')';
            }
        }

        return $backtrace;
    }

    private function isInternalClass(?string $class): bool
    {
        if (!$class) {
            return false;
        }

        $length = false !== $pos = strpos($class, '\\');

        return 'Doctrine' === substr($class, 0, $length ? $pos : strlen($class)) || self::class === $class;
    }
}
