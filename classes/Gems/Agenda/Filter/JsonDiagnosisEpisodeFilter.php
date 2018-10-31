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

use Gems\Agenda\EpisodeOfCare;
use Gems\Agenda\EpisodeFilterAbstract;

/**
 *
 * @package    Gems
 * @subpackage Agenda\Filter
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 30-Oct-2018 15:41:46
 */
class JsonDiagnosisEpisodeFilter extends EpisodeFilterAbstract
{
    /**
     *
     * @var array Optional array keys steps required before
     */
    protected $_filter;

    /**
     *
     * @var string The text value to find
     */
    protected $_value;

    /**
     * Override this function when you need to perform any actions when the data is loaded.
     *
     * Test for the availability of variables as these objects can be loaded data first after
     * deserialization or registry variables first after normal instantiation.
     *
     * That is why this function called both at the end of afterRegistry() and after exchangeArray(),
     * but NOT after unserialize().
     *
     * After this the object should be ready for serialization
     */
    protected function afterLoad()
    {
        if ($this->_data) {
            $this->_value = $this->_data['gaf_filter_text4'];

            $filter[] = $this->_data['gaf_filter_text1'];
            $filter[] = $this->_data['gaf_filter_text2'];
            $filter[] = $this->_data['gaf_filter_text3'];

            $this->_filter = array_filter($filter);
        }
    }

    /**
     *
     * @return string
     */
    protected function getRegex()
    {
        $regexp = $this->toRegexp($this->_value);

        foreach (array_reverse($this->_filter) as $filter) {
            $regexp = '{[^}]*' . $this->toRegexp($filter) . '(:{[^}]*:|:)' . $regexp;
        }
        // \MUtil_Echo::track($regexp);

        return $regexp;
    }

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere()
    {
        $regex = $this->getRegex();

        return "gec_diagnosis_data REGEXP '$regex'";
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\EpisodeOfCare $episode
     * @return boolean
     */
    public function matchEpisode(EpisodeOfCare $episode)
    {
        $data = $episode->getDiagnosisData();
        if (! $data) {
            return true;
        }

        if (!is_string($data)) {
            $data = json_encode($data);
        }

        $regex = $this->getRegex();

        if ((boolean) preg_match($regex, $data)) {
            return true;
        }

        return false;
    }

    /**
     * Translate MySQL LIKE statement to Regex
     *
     * @param string $value
     * @return string
     */
    protected function toRegexp($value)
    {
        return '"[^"]*' . str_replace(['%', '_'], ['[^"]*', '[^"]{1}'], $value) . '[^"]*"';
    }
}
