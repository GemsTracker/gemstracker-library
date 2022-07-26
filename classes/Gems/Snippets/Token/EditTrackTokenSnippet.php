<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Date\Period;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditTrackTokenSnippet extends \Gems\Tracker\Snippets\EditTokenSnippetAbstract
{
    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addFormElements(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $model->set('reset_mail', [
            'label'        => $this->_('Reset sent mail'),
            'description'  => $this->_('Set to zero mails sent'),
            'elementClass' => 'Checkbox',                
        ]);
        
        $onOffFields = array('gr2t_track_info', 'gto_round_description', 'grc_description');
        foreach ($onOffFields as $field) {
            if (! (isset($this->formData[$field]) && $this->formData[$field])) {
                $model->set($field, 'elementClass', 'None');
            }
        }

        parent::addFormElements($bridge, $model);
    }

    /**
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('respondent', 'show', $this->_('Show respondent'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'show', $this->_('Show token'));

        return $links;
    }

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            $this->_items = array_merge(
                    array(
                        'gto_id_respondent',
                        'gr2o_patient_nr',
                        'respondent_name',
                        'gto_id_organization',
                        'gtr_track_name',
                        'gr2t_track_info',
                        'gto_round_description',
                        'gsu_survey_name',
                        'ggp_name',
                        'gro_valid_for_unit',
                        'gto_valid_from_manual',
                        'gto_valid_from',
                        'gto_valid_until_manual',
                        'gto_valid_until',
                        'gto_comment',
                        'gto_mail_sent_date',
                        'gto_mail_sent_num',
                        'reset_mail',
                        'gto_completion_time',
                        'grc_description',
                        'gto_changed',
                        'assigned_by',
                        ),
                    $this->getModel()->getMeta(\MUtil\Model\Type\ChangeTracker::HIDDEN_FIELDS, array())
                    );
            if (! $this->createData) {
                array_unshift($this->_items, 'gto_id_token');
            }
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    public function saveData()
    {
        $model = $this->getModel();

        // \MUtil\EchoOut\EchoOut::track($this->formData);
        if ($this->formData['gto_valid_until'] && Period::isDateType($this->formData['gro_valid_for_unit'])) {
            // Make sure date based units are valid until the end of the day.
            $date = new \MUtil\Date(
                    $this->formData['gto_valid_until'],
                    $model->get('gto_valid_until', 'dateFormat')
                    );
            $date->setTimeToDayEnd();
            $this->formData['gto_valid_until'] = $date;
        }
        
        if (isset($this->formData['reset_mail']) && $this->formData['reset_mail']) {
            $this->formData['gto_mail_sent_date'] = null;
            $this->formData['gto_mail_sent_num']  = 0;
        } else {
            // This value is not editable so it should not be saved.
            unset($this->formData['gto_mail_sent_date'], $this->formData['gto_mail_sent_num']);
        }
        unset($this->formData['reset_mail']);

        // Save the token using the model
        parent::saveData();
        // $this->token->setValidFrom($this->formData['gto_valid_from'], $this->formData['gto_valid_until'], $this->loader->getCurrentUser()->getUserId());

        // \MUtil\EchoOut\EchoOut::track($this->formData);

        // Refresh (NOT UPDATE!) token with current form data
        $updateData['gto_valid_from']         = $this->formData['gto_valid_from'];
        $updateData['gto_valid_from_manual']  = $this->formData['gto_valid_from_manual'];
        $updateData['gto_valid_until']        = $this->formData['gto_valid_until'];
        $updateData['gto_valid_until_manual'] = $this->formData['gto_valid_until_manual'];
        $updateData['gto_comment']            = $this->formData['gto_comment'];
        \MUtil\EchoOut\EchoOut::track($updateData);
        $this->token->refresh($updateData);

        $respTrack = $this->token->getRespondentTrack();
        $userId    = $this->loader->getCurrentUser()->getUserId();
        $changed   = $respTrack->checkTrackTokens($userId, $this->token);

        if ($changed) {
            $this->addMessage(sprintf($this->plural(
                    '%d token changed by recalculation.',
                    '%d tokens changed by recalculation.',
                    $changed
                    ), $changed));
        }
    }
}
