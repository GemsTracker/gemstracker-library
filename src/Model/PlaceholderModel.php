<?php
/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model;

use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModel;
use Zalt\Model\Ra\ArrayModelAbstract;

/**
 * A placeholder array model
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2021 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.9,0
 */
class PlaceholderModel extends ArrayModelAbstract
{
    public function __construct(
        \Zalt\Model\MetaModelLoader $metaModelLoader,
        protected readonly TranslatorInterface $translator,
        protected readonly string $modelName,
        readonly array $fieldArray,
        protected array $data = [],
    )
    {
        $metaModel = new MetaModel($modelName, $metaModelLoader);
        $metaModel->setMulti($fieldArray);

        parent::__construct($metaModel);
    }

    protected function _loadAll(): array
    {
        return $this->data;
    }
}