<?php

namespace Gems\Repository;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Tracker;
use Gems\User\User;
use Laminas\Db\Sql\Predicate\Expression;
use MUtil\Model;
use MUtil\Translate\Translator;
use Zalt\Html\AElement;
use Zalt\Html\HtmlElement;
use Zalt\Late\Late;
use Zalt\Late\LateCall;
use Zalt\Snippets\ModelBridge\TableBridgeAbstract;

class TokenRepository
{
    protected User $currentUser;

    public function __construct(
        protected Tracker $tracker,
        protected Translator $translator, 
        CurrentUserRepository $currentUserRepository)
    {
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Returns a status code => decription array
     *
     * @static $status array
     * @return array
     */
    public function getEveryStatus(): array
    {
        $status = [
            'U' => $this->translator->_('Valid from date unknown'),
            'W' => $this->translator->_('Valid from date in the future'),
            'O' => $this->translator->_('Open - can be answered now'),
            'P' => $this->translator->_('Open - partially answered'),
            'A' => $this->translator->_('Answered'),
            'I' => $this->translator->_('Incomplete - missed deadline'),
            'M' => $this->translator->_('Missed deadline'),
            'D' => $this->translator->_('Token does not exist'),
        ];

        return $status;
    }

    /**
     * An expression for calculating the show status for answers
     *
     * @param int $groupId
     * @return Expression
     */
    public function getShowAnswersExpression(int $groupId): Expression
    {
        return new Expression(sprintf(
            "CASE WHEN gsu_answers_by_group = 0 OR gsu_answer_groups LIKE '%%|%d|%%' THEN 1 ELSE 0 END",
            [$groupId]));
    }

    /**
     * Returns the class to display the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusClass(string $value): string
    {
        switch ($value) {
            case 'A':
                return 'answered';
            case 'I':
                return 'incomplete';
            case 'M':
                return 'missed';
            case 'P':
                return 'partial';
            case 'O':
                return 'open';
            case 'U':
                return 'unknown';
            case 'W':
                return 'waiting';
            default:
                return 'empty';
        }
    }

    /**
     * Returns the description to add to the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusDescription(string $value): string
    {
        $status = $this->getEveryStatus();

        if (isset($status[$value])) {
            return $status[$value];
        }

        return $status['D'];
    }

    /**
     * An expression for calculating the token status
     *
     * @return Expression
     */
    public function getStatusExpression(): Expression
    {
        return new Expression("
            CASE
                WHEN gto_id_token IS NULL OR grc_success = 0 THEN 'D'
                WHEN gto_completion_time IS NOT NULL         THEN 'A'
                WHEN gto_valid_from IS NULL                  THEN 'U'
                WHEN gto_valid_from > CURRENT_TIMESTAMP      THEN 'W'
                WHEN gto_in_source = 1 AND gto_valid_until < CURRENT_TIMESTAMP THEN 'I'
                WHEN gto_valid_until < CURRENT_TIMESTAMP     THEN 'M'
                WHEN gto_in_source = 1                       THEN 'P'
                ELSE 'O'
            END
            ");
    }

    /**
     * Returns the SQL Expression
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusExpressionFor(string $value): string
    {
        switch ($value) {
            case 'D':
                return 'gto_id_token IS NULL OR grc_success = 0';
            case 'A':
                return 'grc_success = 1 AND gto_completion_time IS NOT NULL';
            case 'U':
                return 'grc_success = 1 AND gto_valid_from IS NULL';
            case 'W':
                return 'grc_success = 1 AND gto_valid_from > CURRENT_TIMESTAMP';
            case 'I':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND gto_in_source = 1 AND gto_valid_until < CURRENT_TIMESTAMP';
            case 'M':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND gto_in_source = 0 AND gto_valid_until < CURRENT_TIMESTAMP';
            case 'P':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 1';
            case 'O':
                return 'grc_success = 1 AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 0';
        }
        return '';
    }

    /**
     * Returns the decription to add to the answer
     *
     * @param string $value Character
     * @return HtmlElement
     */
    public function getStatusIcon(string $value): HtmlElement
    {
        $status = $this->getStatusIcons();

        if (isset($status[$value])) {
            return $status[$value];
        }

        return $status['D'];
    }

    /**
     * Returns the status icons in an array
     *
     * @return array
     */
    public function getStatusIcons(): array
    {
        static $status;

        if (is_null($status)) {
            $spanU = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanU->i(['class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true]);
            $spanU->i(['class' => 'fa fa-question fa-stack-1x fa-inverse', 'renderClosingTag' => true]);

            $spanW = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanW->i(['class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true]);
            $spanW->i(['class' => 'fa fa-ellipsis-h fa-stack-1x fa-inverse', 'renderClosingTag' => true]);

            $spanO = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanO->i(['class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true]);
            $spanO->i(['class' => 'fa fa-play fa-stack-1x fa-inverse', 'renderClosingTag' => true]);

            $spanA = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanA->i(['class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true]);
            $spanA->i(['class' => 'fa fa-check fa-stack-1x fa-inverse', 'renderClosingTag' => true]);

            $spanP = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanP->i(['class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true]);
            $spanP->i(['class' => 'fa fa-pause fa-stack-1x fa-inverse', 'renderClosingTag' => true]);

            $spanI = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanI->i(['class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true]);
            $spanI->i(['class' => 'fa fa-stop fa-stack-1x fa-inverse', 'renderClosingTag' => true]);

            $spanM = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanM->i(['class' => 'fa fa-circle fa-stack-2x', 'renderClosingTag' => true]);
            $spanM->i(['class' => 'fa fa-lock fa-stack-1x fa-inverse', 'renderClosingTag' => true]);

            $spanD = Html::create('span', ['class' => 'fa-stack', 'renderClosingTag' => true]);
            $spanD->i(['class' => 'fa fa-times fa-stack-2x', 'renderClosingTag' => true]);

            $status = [
                'U' => $spanU,
                'W' => $spanW,
                'O' => $spanO,
                'A' => $spanA,
                'P' => $spanP,
                'I' => $spanI,
                'M' => $spanM,
                'D' => $spanD,
            ];

            foreach ($status as $val => $stat) {
                //$stat[0]->append($val); // Temp fix until icons work
                $stat->appendAttrib('class', $this->getStatusClass($val));
            }
        }

        return $status;
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $keepCaps Keep the capital letters in the label
     * @param boolean $showAnswers
     * @return \Zalt\Html\AElement
     */
    public function getTokenAnswerLink($url, $patientNr, $organizationId, $tokenId, $tokenStatus, $keepCaps = true, $showAnswers = true): ?AElement
    {
        if ('A' == $tokenStatus || 'P' == $tokenStatus || 'I' == $tokenStatus) {
            $label = $this->translator->_('Answers');

            $link = Html::actionLink($url, $label);

            if ($link) {
                $link->title = sprintf($this->translator->_('See answers for token %s'), strtoupper($tokenId));

                return $link;
            }

        }
        return null;
    }

    /**
     * De a lazy answer link for bridges
     *
     * @param TableBridgeAbstract $bridge
     * @param boolean $keepCaps Keep the capital letters in the label
     * @return LateCall
     */
    public function getTokenAnswerLinkForBridge(TableBridgeAbstract $bridge, MenuSnippetHelper $helper, bool $keepCaps = false): LateCall
    {
        if (! $this->currentUser->hasPrivilege('pr.answer')) {
            //return null;
        }

        $url = $helper->getLateRouteUrl('respondent.tracks.answer', [
            Model::REQUEST_ID  => $bridge->getLate('gto_id_token'),
            Model::REQUEST_ID1 => $bridge->getLate('gr2o_patient_nr'),
            Model::REQUEST_ID2 => $bridge->getLate('gto_id_organization'),
        ]);

        return Late::method($this, 'getTokenAnswerLink',
            $url,
            $bridge->getLate('gr2o_patient_nr'),
            $bridge->getLate('gto_id_organization'),
            $bridge->getLate('gto_id_token'),
            $bridge->getLate('token_status'),
            $keepCaps,
            $bridge->getLate('show_answers')
        );
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $memberType To determine whether the token is answerable by staff
     * @param boolean $keepCaps Keep the capital letters in the label
     */
    public function getTokenAskButton($url, $patientNr, $organizationId, $tokenId, $tokenStatus, $memberType, $keepCaps)
    {
        if ('O' == $tokenStatus || 'P' == $tokenStatus) {
            if ($url && $memberType === 'staff') {
                $label = $this->translator->_('Fill in');

                if ('P' == $tokenStatus) {
                    $label = $this->translator->_('Continue');
                }

                $link = Html::actionLink($url, $label);

                if ($link) {
                    $link->title = sprintf($this->_('Answer token %s'), strtoupper($tokenId));

                    return $link;
                }
            }

            return $this->getTokenCopyLink($tokenId, $tokenStatus);
        }
    }

    /**
     * De a lazy answer link for bridges
     *
     * @param TableBridgeAbstract $bridge
     * @param boolean $forceButton Always show a button
     * @param boolean $keepCaps Keep the capital letters in the label
     * @return \MUtil\Lazy\Call
     */
    public function getTokenAskButtonForBridge(TableBridgeAbstract $bridge, MenuSnippetHelper $helper, bool $forceButton = false, bool $keepCaps = false): LateCall
    {
        if (! $this->currentUser->hasPrivilege('pr.ask')) {
            //return null;
        }

        $url = $helper->getLateRouteUrl('ask.forward', [
            'id' => $bridge->getLate('gto_id_token'),
        ], $bridge);

        if ($forceButton) {
            return Late::method($this, 'getTokenAskButton',
                $url,
                $bridge->getLate('gr2o_patient_nr'),
                $bridge->getLate('gto_id_organization'),
                $bridge->getLate('gto_id_token'),
                $bridge->getLate('token_status'),
                'staff',
                $keepCaps
            );
        }

        return Late::method($this, 'getTokenAskButton',
            $url,
            $bridge->getLate('gr2o_patient_nr'),
            $bridge->getLate('gto_id_organization'),
            $bridge->getLate('gto_id_token'),
            $bridge->getLate('token_status'),
            $bridge->getLate('ggp_member_type'),
            $keepCaps
        );
    }

    /**
     * De a lazy answer link for bridges
     *
     * @param TableBridgeAbstract $bridge
     * @param boolean $forceButton Always show a button
     * @param boolean $keepCaps Keep the capital letters in the label
     * @return \MUtil\Lazy\Call
     */
    public function getTokenAskLinkForBridge(TableBridgeAbstract $bridge, MenuSnippetHelper $helper, $forceButton = false, $keepCaps = false): array
    {
        $method = $this->getTokenAskButtonForBridge($bridge, $helper, $forceButton, $keepCaps);

        if (! $method) {
            $method = Late::method($this, 'getTokenCopyLink',
                $bridge->getLate('gto_id_token'), $bridge->getLate('token_status')
            );
        }

        return [
            $method,
            'class' => Late::method($this, 'getTokenCopyLinkClass',
                $bridge->getLate('token_status'), $bridge->getLate('ggp_member_type')
            ),
        ];
    }

    /**
     * Generate a token item with (in the future) a copy to clipboard button
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @return string
     */
    public function getTokenCopyLink($tokenId, $tokenStatus)
    {
        if ('O' == $tokenStatus || 'P' == $tokenStatus) {
            return $tokenId . ' ';
        }
    }

    /**
     * Generate a token item with (in the future) a copy to clipboard button
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $memberType To determine whether the token is answerable by staff
     * @return string
     */
    public function getTokenCopyLinkClass($tokenStatus, $memberType)
    {
        if (('O' == $tokenStatus || 'P' == $tokenStatus) && $memberType !== 'staff') {
            return 'token';
        }
        return null;
    }

    /**
     * Generate a menu link for email screen
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param boolean $canMail
     * @return \Zalt\Html\AElement
     */
    public function getTokenEmailLink(MenuSnippetHelper $helper, string $tokenId, string $tokenStatus, bool $canMail): ?AElement
    {
        // TODO: The email route does not yet exist 
        if (false && $canMail && ('O' == $tokenStatus || 'P' == $tokenStatus)) {
            $href = $helper->getRouteUrl('email',
            [
                'gto_id_token' => $tokenId,
                'can_be_taken' => 1,
                'can_email'    => 1,
                \Gems\Model::ID_TYPE => 'token',
            ]);

            if ($href) {
                $link = Html::actionLink($href);
            }

            if ($link) {
                $link->title = sprintf($this->translator->_('Send email for token %s'), strtoupper($tokenId));

                return $link;
            }
        }
        return null;
    }

    /**
     * De a lazy answer link for bridges
     */
    public function getTokenEmailLinkForBridge(TableBridgeAbstract $bridge, MenuSnippetHelper $helper): LateCall
    {
        if (! $this->currentUser->hasPrivilege('pr.respondent.track.email')) {
            //return null;
        }

        return Late::method($this, 'getTokenEmailLink',
            $helper, $bridge->getLate('gto_id_token'), $bridge->getLate('token_status'), $bridge->getLate('can_email')
        );
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param boolean $plusLabel Show plus instead of label
     * @return AElement
     */
    public function getTokenShowLink(MenuSnippetHelper $helper, $patientNr, $organizationId, $tokenId, $plusLabel): AElement
    {
        $routeName = 'respondent.tracks.show';
        $label = $this->translator->_('Show');

        if ($plusLabel) {
            $label = $this->translator->_('+');
        }

        $link = Html::actionLink($helper->getRouteUrl($routeName, [
            Model::REQUEST_ID1 => $patientNr,
            Model::REQUEST_ID2 => $organizationId,
            Model::REQUEST_ID => $tokenId,
        ]), $label);

        if ($link) {
            $link->title = sprintf($this->translator->_('Inspect token %s'), strtoupper($tokenId));
        }

        return $link;
    }

    /**
     * De a lazy show link for bridges
     *
     * @param TableBridgeAbstract $bridge
     * @param boolean $plusLabel Show plus instead of label
     * @return AElement
     */
    public function getTokenShowLinkForBridge(TableBridgeAbstract $bridge, MenuSnippetHelper $helper, bool $plusLabel = true): ?AElement
    {
        if (! $this->currentUser->hasPrivilege('respondent.track.show')) {
            //return null;
        }

        $routeName = 'respondent.tracks.show';
        $label = $this->translator->_('Show');

        if ($plusLabel) {
            $label = $this->translator->_('+');
        }

        $link = $helper->getLateRouteUrl($routeName, [
            Model::REQUEST_ID1 => $bridge->getLate('gr2o_patient_nr'),
            Model::REQUEST_ID2 => $bridge->getLate('gto_id_organization'),
            Model::REQUEST_ID => $bridge->getLate('gto_id_token'),
        ]);

        if (isset($link['url'])) {
            return Html::actionLink($link['url'], $label);
        }
        return null;
    }

    /**
     * De a lazy status description text for bridges
     *
     * @param TableBridgeAbstract $bridge
     * @param boolean $addDescription Add the description after the icon
     * @return LateCall
     */
    public function getTokenStatusDescriptionForBridge(TableBridgeAbstract $bridge, $addDescription = false): LateCall
    {
        return Late::method($this, 'getStatusDescription', $bridge->getLate('token_status'));
    }

    /**
     * Generate a menu link for answers pop-up
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param string $patientNr
     * @param string $roundDescr
     * @param string $surveyName
     * @param string $result
     * @return \Zalt\Html\AElement
     */
    public function getTokenStatusLink(MenuSnippetHelper $helper, string $tokenId, string $tokenStatus, string $patientNr, int $organizationId, string $roundDescr, string $surveyName, string $result): ?HtmlElement
    {
        if ($tokenId) {
            $href = $helper->getRouteUrl('respondent.tracks.show', [
                \MUtil\Model::REQUEST_ID  => $tokenId,
                \MUtil\Model::REQUEST_ID1 => $patientNr,
                \MUtil\Model::REQUEST_ID2 => $organizationId,
            ]);

            if (! $href) {
                return null;
            }
            
            $link = Html::create('a', $href);
        } else {
            $link = Html::create('span');
        }

        $link->append($this->getStatusIcon($tokenStatus));
        $link->title = $this->getTokenStatusTitle($tokenId, $tokenStatus, $patientNr, $roundDescr, $surveyName, $result);

        return $link;
    }

    /**
     * De a lazy status show link for bridges
     */
    public function getTokenStatusLinkForBridge(TableBridgeAbstract $bridge, MenuSnippetHelper $helper): LateCall
    {
        if (! $this->currentUser->hasPrivilege('pr.respondent.track.show')) {
            return $this->getTokenStatusShowForBridge($bridge, $helper);
        }

        /*return Late::method($this, 'getTokenStatusLink',
            $helper,
            $bridge->getLate('gto_id_token'), $bridge->getLate('token_status'),
            $bridge->getLate('gr2o_patient_nr'), $bridge->getLate('gto_round_description'),
            $bridge->getLate('gsu_survey_name'), $bridge->getLate('gto_result')
        );*/

        $url = $helper->getLateRouteUrl('respondent.tracks.show', [
            'gto_id_token' => $bridge->getLate('gr2o_patient_nr'),
            \Gems\Model::ID_TYPE => 'token',
        ]);

        $link = Late::iff($url,
            Html::create('a', $url),
            Html::create('span'));

        $link->append($this->getStatusIcon($bridge->getLate('token_status')));
        $link->title = Late::method($this, 'getTokenStatusTitle',
            $bridge->getLate('gto_id_token'), $bridge->getLate('token_status'),
            $bridge->getLate('gr2o_patient_nr'), $bridge->getLate('gto_round_description'),
            $bridge->getLate('gsu_survey_name'), $bridge->getLate('gto_result')
        );

        return $link;
    }

    /**
     * De a status show link
     */
    public function getTokenStatusLinkForTokenId(MenuSnippetHelper $helper, ?string $tokenId):  ?HtmlElement
    {
        $token = $tokenId ? $this->tracker->getToken($tokenId) : null;

        if (! ($tokenId && $token->exists)) {
            return $this->getStatusIcon('D');
        }

        return $this->getTokenStatusLink(
            $helper,
            $tokenId, $token->getStatusCode(),
            $token->getPatientNumber(), $token->getOrganizationId(), $token->getRoundDescription(),
            $token->getSurveyName(), $token->getResult()
        );
    }

    /**
     * De a lazy status show link for bridges
     */
    public function getTokenStatusShowForBridge(TableBridgeAbstract $bridge, MenuSnippetHelper $helper): LateCall
    {
        return Late::method($this, 'getStatusIcon', $bridge->getLate('token_status'));
    }

    /**
     *
     * @param string $tokenId
     * @param string $tokenStatus
     * @param string $patientNr
     * @param string $roundDescr
     * @param string $surveyName
     * @param string $result
     * @return string
     */
    public function getTokenStatusTitle(string $tokenId, string $tokenStatus, string $patientNr, string $roundDescr, string $surveyName, string $result): string
    {
        $title = sprintf($this->translator->_('Token %s: %s'), strtoupper($tokenId), $this->getStatusDescription($tokenStatus));
        if ($roundDescr) {
            $title .= sprintf("\n" . $this->translator->_('Round') . ': %s', $roundDescr);
        }
        if ($surveyName) {
            $title .= "\n" . $surveyName;
        }
        if (!empty($patientNr)) {
            $title .= sprintf("\n" . $this->translator->_('%s: %s'), $this->translator->_('Respondent nr'), $patientNr);
        }
        if ((!empty($result)) && $this->currentUser->hasPrivilege('pr.respondent.result')) {
            $title .= sprintf("\n" . $this->translator->_('%s: %s'), $this->translator->_('Result'), $result);
        }

        return $title;
    }
}