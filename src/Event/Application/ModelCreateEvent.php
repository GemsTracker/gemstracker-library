<?php

declare(strict_types=1);

/**
 *
 * @package    Gem
 * @subpackage Event\Application
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Event\Application;

use Symfony\Contracts\EventDispatcher\Event;
use Zalt\Model\Data\FullDataInterface;

/**
 *
 * @package    Gem
 * @subpackage Event\Application
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class ModelCreateEvent extends Event
{
    public const NAME_START = 'gems.model.create.';

    public string $action;

    public bool $detailed;

    public FullDataInterface $model;

    public ?string $name;

    public function __construct(FullDataInterface $model, string $action, bool $detailed, ?string $name = null)
    {
        $model->getMetaModel();
        $this->model = $model;
        $this->action = $action;
        $this->detailed = $detailed;
        $this->name = self::NAME_START . ($name ?: $model->getName());
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getModel(): FullDataInterface
    {
        return $this->model;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDetailed(): bool
    {
        return $this->detailed;
    }
}
