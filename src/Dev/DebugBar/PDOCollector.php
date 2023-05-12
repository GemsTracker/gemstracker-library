<?php

namespace Gems\Dev\DebugBar;

use DebugBar\DataCollector\TimeDataCollector;

class PDOCollector extends \DebugBar\DataCollector\PDO\PDOCollector
{
    /**
     * Adds a new PDO instance to be collector
     *
     * @param TraceablePDO $pdo
     * @param string $name Optional connection name
     */
    public function addConnection(\PDO $pdo, $name = null)
    {
        if ($name === null) {
            $name = spl_object_hash($pdo);
        }
        if (!($pdo instanceof TraceablePDO)) {
            $pdo = new TraceablePDO($pdo);
        }
        $this->connections[$name] = $pdo;
    }

    /**
     * Collects data from a single TraceablePDO instance
     *
     * @param TraceablePDO $pdo
     * @param TimeDataCollector $timeCollector
     * @param string|null $connectionName the pdo connection (eg default | read | write)
     * @return array
     */
    protected function collectPDO(TraceablePDO|\DebugBar\DataCollector\PDO\TraceablePDO $pdo, TimeDataCollector $timeCollector = null, $connectionName = null)
    {
        if (empty($connectionName) || $connectionName == 'default') {
            $connectionName = 'pdo';
        } else {
            $connectionName = 'pdo ' . $connectionName;
        }
        $stmts = array();
        foreach ($pdo->getExecutedStatements() as $stmt) {
            $stmts[] = array(
                'sql' => $this->renderSqlWithParams ? $stmt->getSqlWithParams($this->sqlQuotationChar) : $stmt->getSql(),
                'row_count' => $stmt->getRowCount(),
                'stmt_id' => $stmt->getPreparedId(),
                'prepared_stmt' => $stmt->getSql(),
                'params' => (object) $stmt->getParameters(),
                'duration' => $stmt->getDuration(),
                'duration_str' => $this->getDataFormatter()->formatDuration($stmt->getDuration()),
                'memory' => $stmt->getMemoryUsage(),
                'memory_str' => $this->getDataFormatter()->formatBytes($stmt->getMemoryUsage()),
                'end_memory' => $stmt->getEndMemory(),
                'end_memory_str' => $this->getDataFormatter()->formatBytes($stmt->getEndMemory()),
                'is_success' => $stmt->isSuccess(),
                'error_code' => $stmt->getErrorCode(),
                'error_message' => $stmt->getErrorMessage()
            );
            if ($timeCollector !== null) {
                $timeCollector->addMeasure($stmt->getSql(), $stmt->getStartTime(), $stmt->getEndTime(), array(), $connectionName);
            }
        }

        return array(
            'nb_statements' => count($stmts),
            'nb_failed_statements' => count($pdo->getFailedExecutedStatements()),
            'accumulated_duration' => $pdo->getAccumulatedStatementsDuration(),
            'accumulated_duration_str' => $this->getDataFormatter()->formatDuration($pdo->getAccumulatedStatementsDuration()),
            'memory_usage' => $pdo->getMemoryUsage(),
            'memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'peak_memory_usage' => $pdo->getPeakMemoryUsage(),
            'peak_memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'statements' => $stmts
        );
    }
}