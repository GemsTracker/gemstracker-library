<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Respondent;

use Gems\Model\MetaModelLoader;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @since      Class available since version 1.0
 */
class RespondentConsentLogModel extends \Gems\Model\SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly RespondentModel $respondentModel,
    )
    {
        parent::__construct('gems__log_respondent_consents', $metaModelLoader, $sqlRunner, $translate);

        $this->applySettings();
    }

    public function applySettings()
    {
        $respondentMetaModel = $this->respondentModel->getMetaModel();

        $fieldOptions = [];
        $valueOptions = [];
        foreach ($this->respondentModel->consentFields as $field) {
            $fieldOptions[$field] = $respondentMetaModel->get($field, 'label');
            $options      = (array) $respondentMetaModel->get($field, 'multiOptions');
            $valueOptions = array_merge($valueOptions, $options);
        }

        $this->metaModel->set('glrc_consent_field', 'label', $this->_('Type'),
            'multiOptions', $fieldOptions);
        $this->metaModel->set('glrc_old_consent', 'label', $this->_('Previous consent'),
            'multiOptions', $valueOptions);
        $this->metaModel->set('glrc_new_consent', 'label', $this->_('New consent'),
            'multiOptions', $valueOptions);
        $this->metaModel->set('glrc_created', 'label', $this->_('Changed on'),
            'dateFormat', $respondentMetaModel->get('gr2o_changed_by', 'dateFormat'),
            'formatFunction', $respondentMetaModel->get('gr2o_changed_by', 'formatFunction'));
        $this->metaModel->set('glrc_created_by', 'label', $this->_('Changed by'),
            'multiOptions', $respondentMetaModel->get('gr2o_changed_by', 'multiOptions'));
    }
}