<?php

namespace Gems\Dev\Clockwork\DataSource;

use Clockwork\DataSource\DataSource;
use Clockwork\Request\Request;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\Sql\Sql;

class LaminasDbDataSource extends DataSource
{
    public function __construct(
        protected readonly Adapter $adapter

    )
    {
    }

    protected function createRunnableQuery(string $sql, array|null $parameters = null): string
    {
        $searchParameters = [];
        $values = [];
        if ($parameters) {
            foreach ($parameters as $parameterName => $parameterValue) {
                $searchParameters[] = ':' . $parameterName;

                $value = $parameterValue;
                if (!is_numeric($parameterValue)) {
                    $value = $this->adapter->getPlatform()->quoteValue($value);
                }
                $values[] = $value;
            }
        }

        return str_replace($searchParameters, $values, $sql);
    }

    protected function getQueriesFromProfiler(Profiler $profiler): array
    {
        $profiles = $profiler->getProfiles();

        $queries = [];
        foreach($profiles as $profile) {

            $parameters = null;
            if ($profile['parameters'] instanceof ParameterContainer) {
                $parameters = $profile['parameters']->getNamedArray();
            }

            $queries[] = [
                'query'      => $this->createRunnableQuery($profile['sql'], $parameters),
                'bindings'   => $parameters,
                'duration'   => $profile['elapse'] * 1000,
                'connection' => 'default',
                'time'       => $profile['start'],
            ];
        }
        return $queries;
    }

    public function resolve(Request $request)
    {
        $profiler = $this->adapter->getProfiler();
        if ($profiler instanceof Profiler) {
            $queries = $this->getQueriesFromProfiler($profiler);
            $request->databaseQueries = array_merge($request->databaseQueries, $queries);
        }

        return $request;
    }

}