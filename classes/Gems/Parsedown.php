<?php

/**
 *
 * @package    Gems
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

/**
 * Allow to parse github links in markdown when project is set
 *
 * @package    Gems
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
class Parsedown extends \Parsedown
{
    public $GitHub = 'https://github.com';
    
     function __construct($projectName = '')
    {
        if (!empty($projectName)) {
            $this->projectName = $projectName;
            $this->InlineTypes['#'] []= 'IssueLink';

            $this->inlineMarkerList .= '#';
        }
    }

    /**
     * Find links to issue on github
     * 
     * organization/repository#<issue> or #<issue>
     * 
     * @param array $excerpt
     * @return array
     */
    protected function inlineIssueLink($excerpt)
    {
        if (preg_match('/([\p{L}\p{N}_-]*)\/?([\p{L}\p{N}_-]*)#(\d+)/ui', $excerpt['context'], $matches, PREG_OFFSET_CAPTURE)) {
            if (!empty($matches[1][0])) {
                $project = $matches[1][0] . '/' . $matches[2][0];
            } else {
                $project = $this->projectName;
            }
            $url = sprintf('%s/%s/issues/%s', $this->GitHub, $project, $matches[3][0]);

            $inline = array(
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => array(
                    'name' => 'a',
                    'text' => '#' . $matches[3][0],
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
            
            return $inline;
        }
    }
}
