<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Audit\AuditLog;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\AppointmentModel;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\Tracker\Respondent;
use Gems\User\User;
use Gems\Util;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentFormSnippet extends ModelFormSnippetAbstract
{
    protected User $currentUser;

    protected ?FullDataInterface $model = null;

    protected Respondent $respondent;

    protected Util $util;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected Model $modelLoader,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        parent::afterSave($changed);

        $model = $this->getModel();
        if ($model instanceof AppointmentModel) {
            $count = $model->getChangedTokenCount();
            if ($count) {
                $this->addMessage(sprintf($this->plural('%d token changed', '%d tokens changed', $count), $count));
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        if (!$this->model instanceof AppointmentModel) {
            $this->model = $this->modelLoader->createAppointmentModel();
            $this->model->applyDetailSettings();
        }
        $metaModel = $this->model->getMetaModel();
        $metaModel->set('gap_admission_time', 'formatFunction', array($this, 'displayDate'));
        $metaModel->set('gap_discharge_time', 'formatFunction', array($this, 'displayDate'));

        return $this->model;
    }
}
