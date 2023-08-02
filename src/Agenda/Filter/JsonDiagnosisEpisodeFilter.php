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
    protected array $_filter;

    /**
     *
     * @var string The text value to find
     */
    protected string $_value;

    protected function afterLoad(): void
    {
        $this->_value = $this->text4;

        $filter = [];
        $filter[] = $this->text1;
        $filter[] = $this->text2;
        $filter[] = $this->text3;

        $this->_filter = array_filter($filter);
    }

    /**
     *
     * @return string
     */
    protected function getRegex(): string
    {
        $regexp = $this->toRegexp($this->_value);

        foreach (array_reverse($this->_filter) as $filter) {
            if ($filter) {
                $regexp = '\\\\{[^\\\\}]*' . $this->toRegexp($filter) . '(:\\\\{[^\\\\}]*:|:)' . $regexp;
            }
        }
        // \MUtil\EchoOut\EchoOut::track($regexp);

        return $regexp;
    }

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    public function getSqlEpisodeWhere(): string
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
    public function matchEpisode(EpisodeOfCare $episode): bool
    {
        $data = $episode->getDiagnosisData();
        if (! $data) {
            return true;
        }

        if (!is_string($data)) {
            $data = json_encode($data);
        }

        $regex = $this->getRegex();

        if ((bool) preg_match($regex, $data)) {
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
    protected function toRegexp(string $value): string
    {
        return '"[^"]*' . str_replace(['%', '_'], ['[^"]*', '[^"]{1}'], $value) . '[^"]*"';
    }
}
