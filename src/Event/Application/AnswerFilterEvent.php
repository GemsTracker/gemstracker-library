<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

class AnswerFilterEvent extends Event
{
    /**
     * AnswerFilterEvent constructor.
     * @param TableBridge $bridge
     * @param DataReaderInterface $model
     * @param array $currentNames
     */
    public function __construct(
        protected readonly TableBridge $bridge,
        protected readonly DataReaderInterface $model,
        protected array $currentNames,
    )
    {
    }

    public function getBridge(): TableBridge
    {
        return $this->bridge;
    }

    public function getModel(): DataReaderInterface
    {
        return $this->model;
    }

    public function getCurrentNames(): array
    {
        return $this->currentNames;
    }

    public function setCurrentNames(array $currentNames): void
    {
        $this->currentNames = $currentNames;
    }
}
