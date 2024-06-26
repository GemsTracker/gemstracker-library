<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModel;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class GemsJoinModel extends \Zalt\Model\Sql\JoinModel
{
    use TranslateableTrait;

    public function __construct(
        string $tableName,
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        string $modelName = null,
        bool $savable = true,
    )
    {
        if ($modelName === null) {
            $modelName = static::class;
        }

        $metaModel = new MetaModel($modelName, $metaModelLoader);
        parent::__construct($metaModel, $sqlRunner);
        $this->startJoin($tableName, $savable);
        $this->translate = $translate;
    }

    /**
     * Helper function to switch from using id column to using a label column
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    protected function switchLabelTo(string $from, string $to)
    {
        $this->metaModel->set($to, [
            'label' => $this->metaModel->get($from, 'label'),
            'order' => $this->metaModel->getOrder($from) + 1,
        ]);
        $this->metaModel->del($from, ['label']);
    }
}
