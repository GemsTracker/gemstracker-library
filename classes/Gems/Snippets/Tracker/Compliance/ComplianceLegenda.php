<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Compliance
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Compliance;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Compliance
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 18-Nov-2019 19:02:35
 */
class ComplianceLegenda extends \MUtil\Snippets\SnippetAbstract
{
    /**
     * The factor to divide maxWidth with to get the emWidth
     *
     * @var float
     */
    protected $emFactor = 1.5;

    /**
     * The maximum number of characters in the rounds column
     * @var int
     */
    protected $maxWidth;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     *
     * @var array [survey, icon, round[, roundSpan]]
     */
    protected $output = [];


    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $table    = new \MUtil\Html\TableElement();
        $table->class = 'compliance timeTable table table-condensed';

        $roundStyle = $this->getRoundStyle();

        $thead = $table->thead();
        $thead->tr()->th($this->_('Track Legend'), ['colspan' => 4]);
        $tr = $thead->tr();
        $tr->th($this->_('Round'), ['class' => 'nextRound', 'style' => $roundStyle]);
        $tr->th(['class' => 'round']);
        $tr->th($this->_('Su'), ['class' => 'round']);
        $tr->th($this->_('Survey'));

        foreach ($this->output as $round) {
            $tr = $table->tr();

            if (isset($round['roundSpan'])) {
                $tr->td(
                        $round['round'],
                        ['rowspan' => $round['roundSpan'], 'class' => 'nextRound', 'style' => $roundStyle]
                        );
            }
            if ($round['icon']) {
                $icon = \MUtil\Html\ImgElement::imgFile($round['icon'], [
                    'alt'   => substr($round['survey'], 0, 2),
                    'title' => $round['survey'],
                    ]);
            } else {
                $icon  = null;
            }
            $tr->td($icon, ['class' => 'round']);
            $tr->td(substr($round['survey'], 0, 2), ['class' => ''], ['class' => 'round']);
            $tr->td($round['survey'], ['class' => '']);
        }

        return $table;
    }

    /**
     * Get the style to set the first column containing round names to
     *
     * @return string
     */
    public function getRoundStyle()
    {
        return sprintf('width: %dem;', intval($this->maxWidth / $this->emFactor));
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
    public function hasHtmlOutput()
    {
        if (! ($this->model && $this->model->getTransformers())) {
            return false;
        }

        $this->maxWidth = strlen($this->_('Round'));

        $oldRound   = null;
        $oldRoundId = null;
        $id         = 0;
        $roundSpan  = 0;

        foreach ($this->model->getItemsOrdered() as $name) {
            $survey = $this->model->get($name, 'survey');
            if ($survey) {
                $icon  = $this->model->get($name, 'roundIcon');
                $round = $this->model->get($name, 'round');
                $roundSpan++;
                $id++;

                if ($oldRound != $round) {
                    if ($oldRoundId) {
                        $this->output[$oldRoundId]['roundSpan'] = $roundSpan;
                    }
                    $oldRoundId = $id;
                    $oldRound   = $round;
                    $roundSpan  = 0;
                }

                $this->output[$id] = [
                    'survey' => $survey,
                    'icon'   => $icon,
                    'round'  => $round,
                ];

                if (strlen($round) > $this->maxWidth) {
                    $this->maxWidth = strlen($round);
                }
            }
        }
        if ($oldRoundId) {
            $this->output[$oldRoundId]['roundSpan'] = ++$roundSpan;
        }
        // \MUtil\EchoOut\EchoOut::track($this->output);

        return (boolean) $this->output;
    }

}
