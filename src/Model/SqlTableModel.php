<?php

namespace Gems\Model;

use MUtil\Translate\Translator;
use Zalt\Base\TranslateableTrait;
use Zalt\Model\MetaModel;
use Zalt\Model\Sql\SqlRunnerInterface;

class SqlTableModel extends \Zalt\Model\Sql\SqlTableModel
{
    use TranslateableTrait;
    public function __construct(
        string $tableName,
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        Translator $translate,
    )
    {
        $this->translate = $translate;
        $metaModel = new MetaModel($tableName, $metaModelLoader);
        parent::__construct($metaModel, $sqlRunner);
    }


}