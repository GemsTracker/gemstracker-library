<?php

namespace Gems\Dev\Clockwork\DataSource;

use Clockwork\DataSource\DataSource;
use Clockwork\Request\Request;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\Sql\Sql;

class ZendDbDataSource extends DataSource
{
    public function __construct(
        protected readonly \Zend_Db_Adapter_Abstract $adapter

    )
    {
    }

    protected function createRunnableQuery(string $sql, array|null $parameters = null): string
    {
        if ($parameters) {
            foreach ($parameters as $parameterValue) {
                $pos = strpos($sql, '?');

                $value = $parameterValue;
                if (!is_numeric($value) && $value !== null) {
                    $value = $this->adapter->quote($value);
                }
                if ($pos !== false) {
                    $sql = substr_replace($sql, (string)$value, $pos, 1);
                }
            }
        }

        return $sql;
    }

    protected function getQueriesFromProfiler(\Zend_Db_Profiler $profiler): array
    {
        $profiles = $profiler->getQueryProfiles();

        $queries = [];
        if ($profiles) {
            foreach ($profiles as $profile) {
                $queries[] = [
                    'query' => $this->createRunnableQuery($profile->getQuery(), $profile->getQueryParams()),
                    'bindings' => $profile->getQueryParams(),
                    'duration' => $profile->getElapsedSecs() * 1000,
                    'connection' => 'ZendDb - default',
                    'time' => $profile->getStartedMicrotime(),
                ];
            }
        }
        return $queries;
    }

    public function resolve(Request $request)
    {
        $profiler = $this->adapter->getProfiler();
        if ($profiler instanceof \Zend_Db_Profiler) {
            $queries = $this->getQueriesFromProfiler($profiler);
            $request->databaseQueries = array_merge($request->databaseQueries, $queries);
        }

        return $request;
    }

}