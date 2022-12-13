<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

/**
 * Describes the use of a track in a text paragraph.
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TrackUsageTextDetailsSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * When true the name is show as a header
     * @var boolean
     */
    protected $showHeader = false;

    /**
     * Optional: the display data of the track shown
     *
     * @var array
     */
    protected $trackData;

    /**
     * Optional, can be source of the $trackId
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * REQUIRED: the id of the track shown
     *
     * Or must be extracted from $trackData or $trackEngine
     *
     * @var int
     */
    protected $trackId;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $html = $this->getHtmlSequence();

        if (! $this->trackData) {
            $html->h2($this->_('Unknown track'));
            $this->addMessage(sprintf($this->_('Unknown track id %s'), $this->trackId));
            return $html;
        }

        if ($this->showHeader) {
            $html->h2(sprintf($this->_('%s track'), $this->trackData['gtr_track_name']));
        }

        if (isset($this->trackData['gtr_date_until']) && $this->trackData['gtr_date_until']) {
            $html->pInfo(
                sprintf(
                    $this->_('This track can be assigned from %s until %s.'),
                    \MUtil\Model::reformatDate($this->trackData['gtr_date_start'], null, 'j M Y'),
                    \MUtil\Model::reformatDate($this->trackData['gtr_date_until'], null, 'j M Y'))
                );

        } else {
            $html->pInfo(
                sprintf(
                    $this->_('This track can be assigned since %s.'),
                    \MUtil\Model::reformatDate($this->trackData['gtr_date_start'], null, 'j M Y'),)
                );
        }

        return $html;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if (! $this->trackData) {
            if (! $this->trackId) {
                if ($this->trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
                    $this->trackId = $this->trackEngine->getTrackId();
                } else {
                    return false;
                }
            }

            $trackModel = new \MUtil\Model\TableModel('gems__tracks');
            $this->trackData = $trackModel->loadFirst(array('gtr_id_track' => $this->trackId));
            
        } elseif (! $this->trackId) {
            $this->trackId = $this->trackData['gtr_id_track'];
        }

        return parent::hasHtmlOutput();
    }
}
