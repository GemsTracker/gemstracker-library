<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Respondent\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Respondent\Export;

use Gems\Html;
use Gems\Tracker\Model\RespondentTrackModel;
use Gems\Tracker\Respondent;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\Token;
use Gems\Util\Translated;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\TableElement;
use Zalt\Late\Late;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Respondent\Export
 * @since      Class available since version 1.0
 */
class TrackSnippet extends TranslatableSnippetAbstract
{
    protected bool $groupSurveys = false;

    protected Respondent $respondent;

    protected RespondentTrack $respondentTrack;

    protected string $tokenExportSnippet = 'Respondent\\Export\\TokenSnippet';

    public function __construct(
        SnippetOptions                          $snippetOptions,
        RequestInfo                             $requestInfo,
        TranslatorInterface                     $translate,
        protected readonly RespondentTrackModel $respondentTrackModel,
        protected readonly SnippetLoader        $snippetLoader,
        protected readonly Translated           $translatedUtil,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        $this->groupSurveys = (bool) $this->requestInfo->getParam('group', false);
    }

    public function addTokenToTable(TableElement $table, Token $token)
    {
        $tr = $table->tr();
        $tr->td($token->getSurveyName());
        $tr->td($token->getRoundDescription());
        $tr->td(strtoupper($token->getTokenId()));
        $tr->td($token->getStatus());;
    }

    public function getHtmlOutput()
    {
        $html      = $this->getHtmlSequence();
        $metaModel = $this->respondentTrackModel->getMetaModel();

        $this->respondentTrackModel->applyDetailSettings($this->respondentTrack->getTrackEngine(), false);
        $metaModel->resetOrder();
        $metaModel->set('gtr_track_name', ['label' => $this->_('Track')]);
        $metaModel->set('gr2t_track_info', [
            'label' => $this->_('Description'),
            'description' => $this->_('Enter the particulars concerning the assignment to this respondent.')
        ]);
        $metaModel->set('assigned_by', ['label' => $this->_('Assigned by')]);

        /** @phpstan-ignore-next-line */
        $metaModel->set('gr2t_start_date', [
            'label' => $this->_('Start'),
            /** @phpstan-ignore-next-line */
            'formatFunction' => $this->translatedUtil->formatDate,
            'default' => new \DateTimeImmutable()
        ]);
        $metaModel->set('gr2t_reception_code');
        $metaModel->set('gr2t_comment', ['label' => $this->_('Comment')]);
        $this->respondentTrackModel->setFilter(['gr2t_id_respondent_track' => $this->respondentTrack->getRespondentTrackId()]);
        $trackData = $this->respondentTrackModel->loadFirst();

        $html->h4($this->_('Track') . ' ' . $trackData['gtr_track_name']);

        $bridge = $this->respondentTrackModel->getBridgeFor('itemTable', array('class' => 'browser table copy-to-clipboard-before'));
        $bridge->setRepeater(Late::repeat([$trackData]));
        /** @phpstan-ignore-next-line */
        $table->th($this->_('Track information'), array('colspan' => 2));
        /** @phpstan-ignore-next-line */
        $table->setColumnCount(1);
        foreach($metaModel->getItemsOrdered() as $name) {
            if ($label = $metaModel->get($name, 'label')) {
                /** @phpstan-ignore-next-line */
                $bridge->addItem($name, $label);
            }
        }

        $tableContainer = Html::create()->div(array('class' => 'table-container'));
        /** @phpstan-ignore-next-line */
        $tableContainer[] = $bridge->getTable();

        $html[] = $tableContainer;
        $html->br();

        $surveys = [];
        $token  = $this->respondentTrack->getFirstToken();
        if ($token) {
            $table = $this->getTokenTable();
            $html[] = $table;
            $html->br();

            $count = 0;
            while ($token) {
                $this->addTokenToTable($table, $token);

                if ($token->isCompleted()) {
                    $surveys[$token->getSurveyId()] = 1;

                    if ((!$this->groupSurveys) || isset($surveys[$token->getSurveyId()])) {
                        $html->append($this->snippetLoader->getSnippet($this->tokenExportSnippet, ['token' => $token]));
                    }
                }

                $token = $token->getNextToken();
            }
        }

        $html->hr();

        return $html;
    }

    public function getTokenTable(): TableElement
    {
        $table = Html::table(['class' => 'browser table copy-to-clipboard-before']);
        $table->th($this->_('Survey'));
        $table->th($this->_('Round'));
        $table->th($this->_('Token'));
        $table->th($this->_('Status'));

        return $table;
    }
}
