<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Dependency;

use Gems\Date\Period;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Gems
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
class TokenDateFormatDependency extends \Zalt\Model\Dependency\DependencyAbstract
{
    protected array $_defaultEffects = ['dateFormat'];

    protected array $_dependentOn = ['gro_valid_for_unit', 'gro_valid_after_unit'];

    protected array $_effecteds = ['gto_valid_from', 'gto_valid_until'];

    protected MetaModelInterface $metaModel;

    public function applyToModel(MetaModelInterface $metaModel)
    {
        $this->metaModel = $metaModel;
        parent::applyToModel($metaModel);
    }

    /**
     * @inheritDoc
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $output = [];

        $checks = array_combine($this->_dependentOn, array_keys($this->_effecteds));
        foreach ($checks as $dependsOn => $effected) {
            if ($context[$dependsOn] && Period::isDateType($context[$dependsOn])) {
                $output[$effected]['dateFormat'] = $this->metaModel->get($effected, 'maybeDateFormat');
            } else {
                $output[$effected]['dateFormat'] = $this->metaModel->get($effected, 'dateFormat');
            }
        }
//        dump($context, $output);

        return $output;
    }
}