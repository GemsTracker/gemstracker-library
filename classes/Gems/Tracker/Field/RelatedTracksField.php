<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class RelatedTracksField extends MultiselectField
{
    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected $_dependsOn = ['gr2t_id_user', 'gr2t_id_organization', 'gr2t_id_respondent_track'];

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected $_effecteds = ['description', 'elementClass', 'multiOptions'];

    /**
     * @var string class for showtracks list display
     */
    protected $displayClass;
    
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * @var bool When true the value is saved with padded seperators
     */
    protected $padSeperators = true;
    
    /**
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * @var \Zend_View
     */
    protected $view;
    
    /**
     * @inheritDoc
     */
    protected function addModelSettings(array &$settings)
    {
        parent::addModelSettings($settings);
        // \MUtil_Echo::track(array_keys($settings));

        $settings['escape'] = false;
        $settings['formatFunction'] = array($this, 'showTracks');
    }

    /**
     * Returns the changes to the model for this field that must be made in an array consisting of
     *
     * <code>
     *  array(setting1 => $value1, setting2 => $value2, ...),
     * </code>
     *
     * By using [] array notation in the setting array key you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array (setting => value)
     */
    public function getDataModelDependyChanges(array $context, $new)
    {
        if ($this->isReadOnly()) {
            $output['elementClass'] = 'Exhibitor';
            $output['description']  = $this->_('No other tracks to select');
        } else {
            $respondentTracks = $this->loader->getTracker()->getRespondentTracks($context['gr2t_id_user'], $context['gr2t_id_organization'], ['gr2t_start_date DESC']);

            // Remove the current track itself as an option
            unset($respondentTracks[$context['gr2t_id_respondent_track']]);

            if ($respondentTracks) {
                $options = [];
                foreach ($respondentTracks as $respondentTrack) {
                    if ($respondentTrack instanceof \Gems_Tracker_RespondentTrack) {
                        $class = $this->getTrackClass($respondentTrack);
                        $label = $this->getTrackLabel($respondentTrack);
                        
                        if ($class) {
                            $label = \MUtil_Html::create('span', $label, ['class' => $class])->render($this->view);
                        }
                        $options[$respondentTrack->getRespondentTrackId()] = $label;
                    }
                }
                $output['multiOptions'] = $options;
            } else {
                $output['description'] = $this->_('No other tracks to select');
                $output['elementClass'] = 'Exhibitor';
            }
            // \MUtil_Echo::track($context,count($respondentTracks), array_keys($respondentTracks));
        }
        
        return $output;
    }

    /**
     * @param \Gems_Tracker_RespondentTrack $respondentTrack
     * @return string The label to display
     */
    public function getTrackClass(\Gems_Tracker_RespondentTrack $respondentTrack)
    {
        return $respondentTrack->getReceptionCode()->isSuccess() ? '' : 'deleted';
    }
    
    /**
     * @param \Gems_Tracker_RespondentTrack $respondentTrack
     * @return string The label to display
     */
    public function getTrackLabel(\Gems_Tracker_RespondentTrack $respondentTrack)
    {
        return $respondentTrack->getTrackName() . ' ' . $respondentTrack->getFieldsInfo();
    }

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave($currentValue, array $fieldData)
    {
        if (is_array($currentValue)) {
            rsort($currentValue, SORT_NUMERIC);
        }

        return parent::onFieldDataSave($currentValue, $fieldData);
    }
    
    /**
     * @param $value
     * @return \MUtil_Html_HtmlElement
     */
    public function showTracks($value)
    {
        if (! is_array($value)) {
            $value = explode('|', $value);
        }
        
        if (! $this->request) {
            $this->request = \Zend_Controller_Front::getInstance()->getRequest();
        }

        $baseUrl      = ['gr2o_patient_nr' => $this->request->getParam(\MUtil_Model::REQUEST_ID1), 'gr2o_id_organization' => $this->request->getParam(\MUtil_Model::REQUEST_ID2)];
        $tracker      = $this->loader->getTracker();
        $showMenuItem = $this->menu->findAllowedController('track', 'show-track');
        
        if ($value) {
            $ul = \MUtil_Html::create('ul', ['class' => $this->displayClass]);
            foreach ($value as $respondentTrackId) {
                // \MUtil_Echo::track($respondentTrackId);
                $respondentTrack = $tracker->getRespondentTrack($respondentTrackId);

                $label = $this->getTrackLabel($respondentTrack);

                $li = $ul->li();
                $li->class = $this->getTrackClass($respondentTrack);
                if ($showMenuItem) {
                    $baseUrl['gr2t_id_respondent_track'] = $respondentTrackId;
                    $li->a($showMenuItem->toHRefAttribute($baseUrl), $label);
                } else {
                    $li->append($label);
                }
            }
        } else {
            $ul = \MUtil_Html::create('span', $this->_('No linked tracks selected.'));
            
        }
        
        return $ul;
    }
}