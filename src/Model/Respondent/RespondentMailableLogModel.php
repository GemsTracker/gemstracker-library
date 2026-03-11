<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Respondent
 */

namespace Gems\Model\Respondent;

use Gems\Model\MetaModelLoader;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @since      Class available since version 2.0.67
 */
class RespondentMailableLogModel extends \Gems\Model\SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly RespondentModel $respondentModel,
    )
    {
        parent::__construct('gems__log_respondent_mailables', $metaModelLoader, $sqlRunner, $translate);

        $this->applySettings();
    }

    public function applySettings()
    {
        $respondentMetaModel = $this->respondentModel->getMetaModel();

        $fieldOptions = [];
        $valueOptions = [];
        foreach ($this->respondentModel->mailableFields as $field) {
            $fieldOptions[$field] = $respondentMetaModel->get($field, 'label');
            $options      = (array) $respondentMetaModel->get($field, 'multiOptions');
            $valueOptions = array_merge($valueOptions, $options);
        }

        $this->metaModel->set('glrm_mailable_field', [
            'label' => $this->_('Type'),
            'multiOptions' => $fieldOptions
        ]);
        $this->metaModel->set('glrm_old_mailable', [
            'label' => $this->_('Previous mail status'),
            'multiOptions' => $valueOptions
        ]);
        $this->metaModel->set('glrm_new_mailable', [
            'label' => $this->_('New mail status'),
            'multiOptions' => $valueOptions
        ]);
        $this->metaModel->set('glrm_created', [
            'label' => $this->_('Changed on'),
            'dateFormat' => $respondentMetaModel->get('gr2o_changed_by', 'dateFormat'),
            'formatFunction' => $respondentMetaModel->get('gr2o_changed_by', 'formatFunction')
        ]);
        $this->metaModel->set('glrm_created_by', [
            'label' => $this->_('Changed by'),
            'multiOptions' => $respondentMetaModel->get('gr2o_changed_by', 'multiOptions')
        ]);
    }
}
