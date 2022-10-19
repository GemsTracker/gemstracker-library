<?php

namespace Gems\Legacy;

use Exception;
use Gems\Db\LegacyDbAdapter\PdoMysqlAdapter;
use Gems\Db\LegacyDbAdapter\PdoSqliteAdapter;
use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LegacyZendDatabaseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /**
         * @var array[]
         */
        $config = $container->get('config');

        if (!isset($config['db'])) {
            throw new Exception('No database configuration found');
        }

        $databaseConfig = $config['db'];

        /**
         * Zend\Db (2.x) uses other configuration names vs \Zend_Db:
         * adapter => driver
         * dbname  => database
         */
        if (!isset($databaseConfig['adapter']) && isset($databaseConfig['driver'])) {
            $databaseConfig['adapter'] = $databaseConfig['driver'];
        }

        if (!isset($databaseConfig['adapter'])) {
            throw new Exception('No database adapter set in config');
        }

        if (!isset($databaseConfig['dbname']) && isset($databaseConfig['database'])) {
            $databaseConfig['dbname'] = $databaseConfig['database'];
        }

        if (!isset($databaseConfig['dbname'])) {
            throw new Exception('No database set in config');
        }

        $adapter = null;
        if ($container->has(\PDO::class)) {
            switch(strtolower($databaseConfig['driver'])) {
                case 'pdo_mysql':
                    $adapter = new PdoMysqlAdapter($databaseConfig);
                    break;
                case 'pdo_sqlite':
                    $adapter = new PdoSqliteAdapter($databaseConfig);
                    break;
                case 'pdo_pgsql':
                    $adapter = new PdoSqliteAdapter($databaseConfig);
                    break;
                case 'pdo_sqlsrv':
                    $adapter = new PdoSqliteAdapter($databaseConfig);
                    break;
                default:
                    $adapter = null;
                    break;
            }

            if ($adapter instanceof \Zend_Db_Adapter_Abstract) {
                $adapter->setConnection($container->get(\PDO::class));
            }
        }

        if ($adapter === null) {
            $adapter = \Zend_Db::factory($databaseConfig['adapter'], $databaseConfig);
        }

        \Zend_Db_Table::setDefaultAdapter($adapter);
        \Zend_Registry::set('db', $adapter);

        return $adapter;
    }
}
