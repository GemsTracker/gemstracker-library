<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Dependency;

use Gems\Menu\MenuSnippetHelper;
use Gems\Usage\UsageCounterInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Snippets\DeleteModeEnum;

/**
 * @package    Gems
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
class UsageDependency extends \Zalt\Model\Dependency\DependencyAbstract
{
    protected string $fieldName;

    public function __construct(
        TranslatorInterface $translate,
        protected readonly MetaModelInterface $metaModel,
        protected readonly UsageCounterInterface $usageCounter,
        protected readonly ?MenuSnippetHelper $menuSnippetHelper = null,
    )
    {
        parent::__construct($translate);

        $this->fieldName = $this->usageCounter->getFieldName();
        $this->setDependsOn($this->fieldName);
        $this->addEffected($this->fieldName, ['readonly', 'disabled']);

        $this->addDependsOn(array_keys(ActivatingYesNoType::getActivatingValues($this->metaModel)));
        $this->addDependsOn(array_keys(ActivatingYesNoType::getDectivatingValues($this->metaModel)));
    }

    /**
     * @inheritDoc
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $output = [];
        if (isset($context[$this->fieldName])) {
            $label = $this->_('Delete');
            if ($this->menuSnippetHelper) {
                $route = $this->menuSnippetHelper->getRelatedRoute('delete');
                if (!$route) {
                    $route = $this->menuSnippetHelper->getRelatedRoute('active-toggle');
                }
            } else {
                $route = null;
            }

            $this->usageCounter->setUsageReport($context[$this->fieldName]);

            if ($this->usageCounter->hasUsage($context[$this->fieldName])) {
                $output = [$this->fieldName => ['readonly' => true, 'disabled' => true]];

                if (ActivatingYesNoType::hasActivation($this->metaModel)) {
                    if (ActivatingYesNoType::isActive($this->metaModel, $context)) {
                        $label = $this->_('Deactivate');
                        $this->usageCounter->setUsageMode(DeleteModeEnum::Deactivate);
                    } else {
                        $label = $this->_('Reactivate');
                        $this->usageCounter->setUsageMode(DeleteModeEnum::Activate);
                    }
                } else {
                    $this->usageCounter->setUsageMode(DeleteModeEnum::Block);
                }
            }

            if ($route) {
                $this->menuSnippetHelper->setMenuItemLabel($route, $label);
            }
        }

        return $output;
    }
}