<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Tracker\Mock\TokenReadonly;
use Gems\User\User;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\TableElement;
use Zalt\Loader\ProjectOverloader;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class CheckTokenEvents extends TranslatableSnippetAbstract
{
    protected User $currentUser;
    
    /**
     * @var \Gems\Tracker\Token
     */
    protected $token;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected Locale $locale,
        protected ProjectOverloader $projectOverloader,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

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
        /**
         * @var TokenReadonly $checkToken
         */
        $checkToken = $this->projectOverloader->create(TokenReadonly::class, $this->token);

        $checkSurvey = $checkToken->getSurvey();

        $html = $this->getHtmlSequence();

        $html->h1(sprintf($this->_('Token event overview for token %s'), $checkToken->getTokenId()));

        $html->h2($this->_('Token info'));
        
        $tInfo = [
            $this->_('Token') => $checkToken->getTokenId(),
            $this->_('Track') => $checkToken->getTrackName(),
            $this->_('Round') => $checkToken->getRoundDescription(),
            $this->_('Survey') => $checkToken->getSurveyName(),
            $this->_('Status') => $checkToken->getStatus(),
            ];
        $html->div($this->showArrayTable($tInfo, $this->_('Token settings')), ['class' => 'leftFloat']);
        $html->div($this->showArrayTable(array_filter($checkToken->getRawAnswers()), $this->_('Current raw answers')), ['class' => 'leftFloat']);
        
        $beforeEvent = $checkSurvey->getSurveyBeforeAnsweringEvent();
        $html->h2($this->_('Before answering'), ['style' => 'clear: both;']);
        if ($beforeEvent) {
            $html->pInfo($this->_('This token has this before answering event: '))
                ->strong($beforeEvent->getEventName());
            
            $answers = $checkToken->getRawAnswers();
            $checkToken->getUrl($this->locale->getLanguage(), $this->currentUser->getUserId());
            
            $currentAnswers = $checkToken->getMockChanges('setRawAnswers', 'answers');
            $currentLog     = $checkToken->getMockChanges('log');

            // \MUtil\EchoOut\EchoOut::track($checkToken->getMockChanges());
            $html->h3($this->_('Data changed by this event'));
            if ($currentAnswers) {
                $html->div($this->showArrayTable($currentAnswers), ['class' => 'leftFloat']);
            } else {
                if ($checkToken->isCompleted()) {
                    $html->pInfo($this->_('This event has been completed and this function would currently not change any data for this token.'));
                } else {
                    $html->pInfo($this->_('This event currently does not change any data for this token.'));
                }
            }
            if ($currentLog) {
                $div = $html->div(['class' => 'leftFloat']);
                $div->pInfo($this->_('Actions currently taken by this event:'));
                $div->ol($currentLog);
            }
            // \MUtil\EchoOut\EchoOut::track($checkToken->getMockChanges());
            
            $checkToken->unsetRawAnswers();
            $checkToken->getUrl($this->locale->getLanguage(), $this->currentUser->getUserId());
            $emptyAnswers = $checkToken->getMockChanges('setRawAnswers', 'answers');
            $emptyLog     = $checkToken->getMockChanges('log');
            if (($emptyAnswers && ($emptyAnswers != $currentAnswers)) || ($emptyLog && ($emptyLog != $currentLog))) {
                $html->h3($this->_('Data that would be changed if empty'), ['style' => 'clear: both;']);
                if ($checkToken->getCopiedFrom()) {
                    $html->pInfo(sprintf($this->_('The answers in this token will be prefilled from the previous token %s.'), $checkToken->getCopiedFrom()));
                }
                $html->div($this->showArrayTable($emptyAnswers), ['class' => 'leftFloat']);
                if ($emptyLog) {
                    $div = $html->div(['class' => 'leftFloat']);
                    $div->pInfo($this->_('Actions taken by this event while empty:'));
                    $div->ol($emptyLog);
                }
            }
            // \MUtil\EchoOut\EchoOut::track($checkToken->getMockChanges());
            
        } else {
            $html->pInfo($this->_('This token has no before answering event.'));
        }
        $html->div(['style' => 'clear: both;', 'renderClosingTag' => true,]);

        // \MUtil\EchoOut\EchoOut::track($checkToken->getMockChanges());
        
        return $html;
    }
    
    protected function showArrayTable(array $data, $caption = false)
    {
        $table = new TableElement();
        $table->class = 'displayer table table-condensed table-bordered';
        
        if ($caption) {
            $tr = $table->thead()->tr();
            $tr->td(['colspan' => 2])->strong($caption);
        }
        
        $tr = $table->thead()->tr();
        $tr->th($this->_('Field'));
        $tr->th($this->_('Value'));
        foreach ($data as $key => $value) {
            $tr = $table->tr();
            $tr->td($key);
            $tr->td($value);
        }
        
        return $table; 
    }
}