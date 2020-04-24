<?php

namespace Gems\Event\Application;

use Symfony\Component\EventDispatcher\Event;

class GetDatabasePaths extends NamedArrayEvent
{
    const NAME = 'gems.databasepaths.get';

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var array list of all currently assigned database paths
     */
    protected $paths;

    public function __construct(\Zend_Db_Adapter_Abstract $db, $paths = [])
    {
        $this->db = $db;
        $this->paths = $paths;
    }

    public function addPath($name, $path)
    {
        $this->paths[] = array(
            'path' => $path,
            'name' => $name,
            'db'   => $this->db,
        );
    }

    public function getPaths()
    {
        return $this->paths;
    }
}
