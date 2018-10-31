<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda\Filter
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\FilterModelDependencyAbstract;

/**
 *
 * @package    Gems
 * @subpackage Agenda\Filter
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 30-Oct-2018 15:35:50
 */
class JsonDiagnosisDependency extends FilterModelDependencyAbstract
{
    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function calcultateName($value, $isNew = false, $name = null, array $context = array())
    {
        $filter[] = $context['gaf_filter_text1'];
        $filter[] = $context['gaf_filter_text2'];
        $filter[] = $context['gaf_filter_text3'];
        $filter = array_filter($filter);

        if ($filter) {
            if (1 == count($filter)) {
                return sprintf(
                        $this->_('Episode diagnosis contains %s after key: %s'),
                        $context['gaf_filter_text4'],
                        reset($filter)
                        );
            } else {
                return sprintf(
                        $this->_('Episode diagnosis contains %s after keys: %s'),
                        $context['gaf_filter_text4'],
                        implode(', ', $filter)
                        );
            }
        } else {
            return sprintf($this->_('Diagnosis data contains %s'), $context['gaf_filter_text4']);
        }
    }

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    public function getFilterClass()
    {
        return 'JsonDiagnosisEpisodeFilter';
    }

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    public function getFilterName()
    {
        return $this->_('Diagnosis Data match');
    }

    /**
     * Get the settings for the gaf_filter_textN fields
     *
     * Fields not in this array are not shown in any way
     *
     * @return array gaf_filter_textN => array(modelFieldName => fieldValue)
     */
    public function getTextSettings()
    {
        $description = sprintf($this->_(
                "Use the %%-sign to search for zero or more random characters and an _ for a single random character."
                ));

        return [
            'gaf_filter_text1' => [
                'label'       => $this->_('1st optional key'),
                'description' => $description,
                'required'    => false,
                ],
            'gaf_filter_text2' => [
                'label'       => $this->_('2nd optional key'),
                'description' => $description,
                'required'    => false,
                ],
            'gaf_filter_text3' => [
                'label'       => $this->_('3rd optional key'),
                'description' => $description,
                'required'    => false,
                ],
            'gaf_filter_text4' => [
                'label'       => $this->_('Data value'),
                'description' => $description,
                'required'    => true,
                ],
            ];
    }
}
