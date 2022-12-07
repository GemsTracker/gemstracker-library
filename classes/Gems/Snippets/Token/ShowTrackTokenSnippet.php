<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\Bridge\ThreeColumnTableBridge;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\Tracker\Snippets\ShowTokenSnippetAbstract;
use Gems\User\User;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Display snippet for standard track tokens
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowTrackTokenSnippet extends ShowTokenSnippetAbstract
{
    protected User $currentUser;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        Tracker $tracker,
        protected TokenRepository $tokenRepository,
        protected MenuSnippetHelper $menuSnippetHelper,
        protected CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $tracker);
        $this->currentUser = $this->currentUserRepository->getCurrentUser();
    }

    /**
     * Adds third block to fifth group group of rows showing completion data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param ThreeColumnTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addCompletionBlock(ThreeColumnTableBridge $bridge)
    {
        // COMPLETION DATE
        $fields = array();
        if ($this->token->getReceptionCode()->hasDescription()) {
            $bridge->addMarkerRow();
            $fields[] = 'grc_description';
        }
        $fields[] = 'gto_completion_time';
        if ($this->token->isCompleted()) {
            $fields[] = 'gto_duration_in_sec';
        }
        if ($this->token->hasResult()) {
            $fields[] = 'gto_result';
        }

        $items = [
            [
                'route' => 'respondent.tracks.correct',
                'label' => $this->_('Correct answers'),
                'disabled' => $this->token->isCompleted(),
            ],
            [
                'route' => 'respondent.tracks.delete',
                'label' => $this->_('Delete'),
            ],
        ];

        $buttons = [];
        foreach($items as $item) {
            $url = $this->menuSnippetHelper->getRouteUrl($item['route'], $this->requestInfo->getRequestMatchedParams());
            if ($url) {
                $buttons[$item['route']] = Html::actionLink($url, $item['label']);
            }
        }

        $fields[] = $buttons;

        $bridge->addWithThird($fields);

        return true;
    }

    /**
     * Adds second block to fifth group group of rows showing contact data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param ThreeColumnTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addContactBlock(ThreeColumnTableBridge $bridge)
    {
        // E-MAIL

        $buttons = Html::actionLink($this->menuSnippetHelper->getRouteUrl('respondent.tracks.email', $this->requestInfo->getRequestMatchedParams()), $this->_('E-mail now!'));

        $bridge->addWithThird('gto_mail_sent_date', 'gto_mail_sent_num', $buttons);

        return true;
    }

    /**
     * Adds first group of rows showing token specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param ThreeColumnTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addHeaderGroup(ThreeColumnTableBridge $bridge)
    {
        $bridge->addItem('gto_id_token', $this->_('Token'), array('colspan' => 1.5));
        $copiedFrom = $this->token->getCopiedFrom();
        if ($copiedFrom) {
            $bridge->tr();
            $bridge->tdh($this->_('Token copied from'));
            $bridge->td(array('colspan' => 2, 'skiprowclass' => true))
                ->a([\MUtil\Model::REQUEST_ID => $copiedFrom], $copiedFrom);
        }
        $copiedTo = $this->token->getCopiedTo();
        if ($copiedTo) {
            $bridge->tr();
            $bridge->tdh($this->_('Token copied to'));
            $td = $bridge->td(array('colspan' => 2, 'skiprowclass' => true));
            foreach ($copiedTo as $copy) {
                $td->a([\MUtil\Model::REQUEST_ID => $copy], $copy);
                $td->append(' ');
            }
        }

        // Token status
        $bridge->tr();
        $bridge->tdh($this->_('Status'));
        $td = $bridge->td(
            ['colspan' => 2, 'skiprowclass' => true],
            \Zalt\Html\Html::raw($this->tokenRepository->getTokenStatusShowForBridge($bridge, $this->menuSnippetHelper)),
            ' ',
            \Zalt\Html\Html::raw($this->tokenRepository->getTokenStatusDescriptionForBridge($bridge))
        );

        $items = [
            [
                'route' => 'ask.take',
                'label' => $this->_('Take'),
                'disabled' => (!$this->token->getReceptionCode()->isSuccess() || $this->token->isCompleted() || !$this->token->isCurrentlyValid()),
            ],
            /*[
                'route' => 'pdf.show',
                'label' => $this->_('PDF'),
            ],*/
            [
                'route' => 'respondent.tracks.questions',
                'label' => $this->_('Preview'),
            ],
            [
                'route' => 'respondent.tracks.answer',
                'label' => $this->_('Answers'),
                'disabled' => $this->token->isCurrentlyValid(),
            ],
            [
                'route' => 'respondent.tracks.answer-export',
                'label' => $this->_('Answer export'),
                'disabled' => $this->token->isCurrentlyValid(),
            ],
        ];

        $buttons = [];
        foreach($items as $item) {
            $url = $this->menuSnippetHelper->getRouteUrl($item['route'], $this->requestInfo->getRequestMatchedParams());
            if ($url) {
                if (isset($item['disabled']) && $item['disabled'] === true) {
                    $buttons[$item['route']] = Html::actionDisabled($item['label']);
                    continue;
                }
                $buttons[$item['route']] = Html::actionLink($url, $item['label']);
            }
        }

        if ($buttons) {
            $bridge->tr();
            $bridge->tdh($this->_('Actions'));
            $bridge->td($buttons, ['colspan' => 2, 'skiprowclass' => true]);
        }

        //$buttons = $this->routeHelper->getActionLinksFromRouteItems($items, $this->requestInfo->getRequestMatchedParams());

        //if (count($buttons)) {
            /*if (isset($buttons['ask.take']) && ($buttons['ask.take'] instanceof \MUtil\Html\HtmlElement)) {
                if ('a' == $buttons['ask.take']->tagName) {
                    $buttons['ask.take'] = $tData->getTokenAskButtonForBridge($bridge, true, true);
                }
            }
            if (isset($buttons['track.answer']) && ($buttons['track.answer'] instanceof \MUtil\Html\HtmlElement)) {
                if ('a' == $buttons['track.answer']->tagName) {
                    $buttons['track.answer'] = $tData->getTokenAnswerLinkForBridge($bridge, true);
                }
            }*/

            //$bridge->tr();
            //$bridge->tdh($this->_('Actions'));
            //$bridge->td($buttons, array('colspan' => 2, 'skiprowclass' => true));
        //}

        return true;
    }

    /**
     * Adds last group of rows cleaning up whatever is left to do in adding links etc.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param ThreeColumnTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addLastitems(ThreeColumnTableBridge $bridge)
    {
        $items = [
            [
                'route' => 'respondent.show',
                'label' => $this->_('Show patient'),
            ],
            [
                'route' => 'respondent.tracks.index',
                'label' => $this->_('Show tracks'),
            ],
            [
                'route' => 'respondent.tracks.show-track',
                'label' => $this->_('Show track'),
                'parameters' => [
                    Model::RESPONDENT_TRACK => $this->token->getRespondentTrackId(),
                ]
            ],
            /*[
                'route' => 'respondent.tracks.undelete',
                'label' => $this->_('Undelete!'),
                'label'
            ],*/
            [
                'route' => 'respondent.tracks.check-token',
                'label' => $this->_('Token check'),
            ],
            [
                'route' => 'respondent.tracks.check-token-answers',
                'label' => $this->_('(Re)check answers'),
            ],
        ];

        $links = [];
        foreach($items as $item) {
            $url = $this->menuSnippetHelper->getRouteUrl($item['route'], $this->requestInfo->getRequestMatchedParams());
            if ($url) {
                $links[$item['route']] = Html::actionLink($url, $item['label']);
            }
        }

        if ($links) {
            $bridge->tfrow($links, array('class' => 'centerAlign'));
        }

        foreach ($bridge->tbody() as $row) {
            if (isset($row[1]) && ($row[1] instanceof \Zalt\Html\HtmlElement)) {
                if (isset($row[1]->skiprowclass)) {
                    unset($row[1]->skiprowclass);
                } else {
                    $row[1]->appendAttrib('class', $bridge->row_class);
                }
            }
        }

        return true;
    }

    /**
     * Adds second group of rows showing patient specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param ThreeColumnTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addRespondentGroup(ThreeColumnTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $params = [
            'id1' => 'gr2o_patient_nr',
            'id2' => 'gr2o_id_organization',
        ];

        $href = $this->menuSnippetHelper->getLateRouteUrl('respondent.show', $params);

        $model->set('gr2o_patient_nr', 'itemDisplay', \Zalt\Html\Html::create('a', $href));

        // ThreeColumnTableBridge->add()
        $bridge->add('gr2o_patient_nr');
        if (! $this->currentUser->isFieldMaskedWhole('respondent_name')) {
            $bridge->add('respondent_name');
        }

        return true;
    }

    /**
     * Adds forth group of rows showing round specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param ThreeColumnTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addRoundGroup(ThreeColumnTableBridge $bridge)
    {
        $bridge->add('gsu_survey_name');
        $bridge->add('gto_round_description');
        $bridge->add('ggp_name');

        return true;
    }

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(DetailTableBridge $bridge, DataReaderInterface $model)
    {
        // \MUtil\Model::$verbose = true;

        // Don't know why, but is needed now

        if ($bridge instanceof ThreeColumnTableBridge) {
            if ($this->addHeaderGroup($bridge)) {
                $bridge->addMarkerRow();
            }
            if ($this->addRespondentGroup($bridge, $model)) {
                $bridge->addMarkerRow();
            }
            if ($this->addTrackGroup($bridge, $model)) {
                $bridge->addMarkerRow();
            }
            if ($this->addRoundGroup($bridge)) {
                $bridge->addMarkerRow();
            }

            $this->addValidFromBlock($bridge);
            $this->addContactBlock($bridge);
            $this->addCompletionBlock($bridge);

            $this->addLastitems($bridge);
        }
    }

    /**
     * Adds third group of rows showing track specific data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addTrackGroup(ThreeColumnTableBridge $bridge, ModelAbstract $model)
    {
        $params = [
            'id1' => 'gr2o_patient_nr',
            'id2' => 'gr2o_id_organization',
            Model::RESPONDENT_TRACK => 'gto_id_respondent_track',
        ];

        $href = $this->menuSnippetHelper->getLateRouteUrl('respondent.tracks.show-track', $params);
        $model->set('gtr_track_name', 'itemDisplay', \Zalt\Html\Html::create('a', $href));

        // ThreeColumnTableBridge->add()
        $bridge->add('gtr_track_name');
        $bridge->add('gr2t_track_info');
        $bridge->add('assigned_by');

        return true;
    }

    /**
     * Adds first block to fifth group group of rows showing valid from data
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Menu\MenuList $links
     * @return boolean True when there was row output
     */
    protected function addValidFromBlock(ThreeColumnTableBridge $bridge)
    {
        // Editable part (INFO / VALID FROM / UNTIL / E-MAIL

        $links = [
            Html::actionLink($this->menuSnippetHelper->getRouteUrl('respondent.tracks.edit', $this->requestInfo->getRequestMatchedParams()), $this->_('edit'))
        ];

        $bridge->addWithThird(
            'gto_valid_from_manual',
            'gto_valid_from',
            'gto_valid_until_manual',
            'gto_valid_until',
            'gto_comment',
            $links,
        );

        return true;
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        return $this->_('Show token');
    }
}
