<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Dependency;

use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Snippets\DeleteModeEnum;

/**
 * @package    Gems
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
class ActivationDependency extends \Zalt\Model\Dependency\DependencyAbstract
{
    /**
     * No autosubmit!
     *
     * @var array
     */
    protected array $autoSubmitSettings = [];

    public function __construct(
        TranslatorInterface $translate,
        protected readonly MetaModelInterface $metaModel,
        protected readonly ?MenuSnippetHelper $menuSnippetHelper = null,
    )
    {
        parent::__construct($translate);

        $this->addDependsOn(array_keys(ActivatingYesNoType::getActivatingValues($this->metaModel)));
        $this->addDependsOn(array_keys(ActivatingYesNoType::getDeactivatingValues($this->metaModel)));
    }

    /**
     * @inheritDoc
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $output = [];

        if (ActivatingYesNoType::hasActivation($this->metaModel)) {
            if ($this->menuSnippetHelper) {
                $route = $this->menuSnippetHelper->getRelatedRoute('delete');
                if (!$route) {
                    $route = $this->menuSnippetHelper->getRelatedRoute('active-toggle');
                }
            } else {
                $route = null;
            }

            if (ActivatingYesNoType::isActive($this->metaModel, $context)) {
                $label = $this->_('Deactivate');
            } else {
                $label = $this->_('Reactivate');
            }
            if ($route && $label) {
                $this->menuSnippetHelper->setMenuItemLabel($route, $label);
            }

        }

        return $output;
    }
}