<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Bridge;

use Zalt\Model\MetaModelInterface;
use Zalt\Validator\NoTags;

/**
 * @package    Gems
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
class GemsValidatorBridge extends \Zalt\Model\Bridge\Laminas\LaminasValidatorBridge
{
    public function getTypeValidatorsText(MetaModelInterface $metaModel, string $name): array
    {
        $output = [];

        if ($metaModel->getWithDefault($name, 'autoInsertNoTagsValidator', true)) {
            $output[NoTags::class] = [NoTags::class];
        }

        return $output;
    }

    protected function loadDefaultTypeCompilers()
    {
        parent::loadDefaultTypeCompilers();
        $this->setTypeClassCompiler(MetaModelInterface::TYPE_STRING, [$this, 'getTypeValidatorsText']);
    }
}