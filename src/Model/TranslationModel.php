<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class TranslationModel extends SqlTableModel
{
    const KEY_COLUMN = "CONCAT(gtrs_table, '_', gtrs_field, '_', gtrs_keys)";

    public function __construct(MetaModelLoader $metaModelLoader, SqlRunnerInterface $sqlRunner, TranslatorInterface $translate)
    {
        parent::__construct('gems__translations', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gtrs');
        $this->addColumn(self::KEY_COLUMN, 'key');
    }
}