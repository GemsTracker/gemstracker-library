<?php


namespace Gems\Tracker\Field;


use Gems\Tracker\Field\FieldAbstract;

class TrackField extends FieldAbstract
{

    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected $_dependsOn = array('gr2t_id_user', 'gr2t_id_organization', 'gr2t_id_respondent_track');

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected $_effecteds = array('multiOptions');

    /**
     * @var $db
     */
    protected $db;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings)
    {
        $empty = $this->util->getTranslated()->getEmptyDropdownArray();

        $settings['elementClass'] = 'Select';
        $settings['multiOptions'] = $empty + $this->getLookup();
        $settings['formatFunction'] = array($this, 'showTrack');
    }
    
    public function calculateFieldInfo($currentValue, array $fieldData)
    {
        // Always leave empty
        return false;
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
            return null;
        }
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();
        $tracks = $this->getLookup($context['gr2t_id_user'], $context['gr2t_id_organization']);
        unset($tracks[$context['gr2t_id_respondent_track']]);
        $output['multiOptions'] = $empty + $tracks;

        return $output;
    }

    /**
     * Return the lookup array for this field
     *
     * @param int $organizationId Organization Id
     * @return array
     */
    protected function getLookup($respondentId = null, $organizationId = null)
    {
        if ($respondentId !== null && $organizationId !== null) {

            $respondentTracks = $this->loader->getTracker()->getRespondentTracks($respondentId, $organizationId);

            $respondentTrackPairs = [];
            foreach($respondentTracks as $respondentTrack) {
                $name = $respondentTrack->getTrackName();
                $startDate = $respondentTrack->getStartDate();
                if ($startDate) {
                    $name .= ' (' . $startDate->toString('dd-MM-yyyy') . ')';
                }
                $respondentTrackPairs[$respondentTrack->getRespondentTrackId()] = $name;
            }

            return $respondentTrackPairs;
        }
        return [];
    }

    /**
     * Display a respondent track as text
     *
     * @param $value
     * @return string
     */
    public function showTrack($value)
    {
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();
        if (! $value || in_array($value, $empty)) {
            return null;
        }
        if (!$value instanceof \Gems\Tracker\RespondentTrack && is_numeric($value)) {
            $value = $this->loader->getTracker()->getRespondentTrack($value);
        }

        if (!$value instanceof \Gems\Tracker\RespondentTrack) {
            return null;
        }

        $name = $value->getTrackName();
        /*$startDate = $value->getStartDate();
        if ($startDate) {
            $name .= ' (' . $startDate->toString('dd-MM-yyyy') . ')';
        }*/

        if (! $this->menu instanceof \Gems\Menu) {
            $this->menu = $this->loader->getMenu();
        }

        $menuItem = $this->menu->findAllowedController('track', 'show-track');
        if ($menuItem instanceof \Gems\Menu\SubMenuItem) {
            if (!$this->request) {
                $this->request = \Zend_Controller_Front::getInstance()->getRequest();
            }
            $href = $menuItem->toHRefAttribute([
                'gr2t_id_respondent' => $value->getPatientNumber(),
                'gr2t_id_organization' => $value->getOrganizationId(),
                'gr2t_id_respondent_track' => $value->getRespondentTrackId(),
            ], $this->request);
            if ($href) {
                return \MUtil\Html::create('a', $href, $name);
            }
        }

        return $name;
    }
}