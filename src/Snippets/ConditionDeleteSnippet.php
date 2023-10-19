<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\ConditionModel;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class ConditionDeleteSnippet extends ModelConfirmSnippetAbstract
{
    protected ConditionModel|null $model = null;

    /**
     *
     * @var int
     */
    protected int $conditionId;

    /**
     * The number of times someone started answering a round in this track
     *
     * @var int
     */
    protected int $useCount = 0;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuSnippetHelper,
        protected readonly Model $modelLoader,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuSnippetHelper);
    }

    public function checkModel(FullDataInterface $dataModel): void
    {
        if ($dataModel instanceof ConditionModel) {
            $this->useCount = $dataModel->getUsedCount($this->conditionId);

            if ($this->useCount) {
                $this->messenger->addMessage(sprintf($this->plural(
                    'This condition has been used %s time.', 'This condition has been used %s times.',
                    $this->useCount
                ), $this->useCount));
                $this->messenger->addMessage($this->_('This condition cannot be deleted, only deactivated.'));
                // $this->displayTitle   = $this->_('Deactivate condition');
                parent::checkModel($dataModel);
            }
        }

    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof ConditionModel) {
            $this->model = $this->modelLoader->getConditionModel();
            $this->model->applyEditSettings();
        }

        return $this->model;
    }
}