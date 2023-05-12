<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda\Filter;

use Gems\Agenda\EpisodeFilterAbstract;
use Gems\Agenda\EpisodeOfCare;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:02:33
 */
class DiagnosisEpisodeFilter extends EpisodeFilterAbstract
{
    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere()
    {
        $text = $this->_data['gaf_filter_text1'];
        if ($text) {
            return "gec_diagnosis LIKE '$text'";
        } else {
            return "(gec_diagnosis IS NULL OR gec_diagnosis = '')";
        }
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\EpisodeOfCare $episode
     * @return boolean
     */
    public function matchEpisode(EpisodeOfCare $episode)
    {
        if (! $this->_data['gaf_filter_text1']) {
            return ! $episode->getDiagnosis();
        }

        $regex = '/' . str_replace(array('%', '_'), array('.*', '.{1,1}'),$this->_data['gaf_filter_text1']) . '/i';

        return (boolean) preg_match($regex, $episode->getDiagnosis());
    }
}
