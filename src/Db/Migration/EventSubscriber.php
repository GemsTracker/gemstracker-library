<?php

namespace Gems\Db\Migration;

use Gems\Event\Application\CreateTableMigrationEvent;
use Gems\Event\Application\RunPatchMigrationEvent;
use Gems\Event\Application\RunSeedMigrationEvent;
use Gems\Event\DatabaseMigrationEvent;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Adapter $adapter,
    )
    {}


    public static function getSubscribedEvents()
    {
        return [
            CreateTableMigrationEvent::class => [
                'addToMigrationLog'
            ],
            RunPatchMigrationEvent::class => [
                'addToMigrationLog'
            ],
            RunSeedMigrationEvent::class => [
                'addToMigrationLog'
            ],
        ];
    }

    public function addToMigrationLog(DatabaseMigrationEvent $event)
    {
        $table = new TableGateway('gems__migration_logs', $this->adapter);
        $table->insert([
            'gml_name' => $event->getName(),
            'gml_type' => $event->getType(),
            'gml_version' => $event->getVersion(),
            'gml_module' => $event->getModule(),
            'gml_status' => $event->getStatus(),
            'gml_duration' => $event->getDuration(),
            'gml_sql' => $event->getSql(),
            'gml_comment' => $event->getComment(),
            'gml_created' => new Expression('NOW()'),
        ]);
    }
}