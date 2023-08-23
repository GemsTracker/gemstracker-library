<?php

namespace Gems\Model;

use Zalt\Base\TranslateableTrait;
use Zalt\Model\MetaModel;
use Zalt\Model\Sql\SqlRunnerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SqlTableModel extends \Zalt\Model\Sql\SqlTableModel
{
    use TranslateableTrait;

    public function __construct(
        string $tableName,
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
    )
    {
        $this->translate = $translate;
        $metaModel = new MetaModel($tableName, $metaModelLoader);
        parent::__construct($metaModel, $sqlRunner);
    }
}