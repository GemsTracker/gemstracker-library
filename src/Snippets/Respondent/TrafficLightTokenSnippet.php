<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Menu\SubMenuItem;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Model\Type\GemsDateTimeType;
use Gems\Model\Type\GemsDateType;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\HtmlElement;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\JoinModel;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Show the track in a different way, ordered by round and group showing
 * traffic light color indicating the status of a token and uses inline
 * answer display.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class TrafficLightTokenSnippet extends \Gems\Snippets\Token\RespondentTokenSnippet
{
    /**
     * Set a fixed model filter.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     * /
    protected $_fixedFilter     = array(
        //'gto_valid_from <= NOW()'
    ); // */

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort       = array(
        'gr2t_start_date'         => SORT_DESC,
        'gto_id_respondent_track' => SORT_DESC,
        'gto_round_order'         => SORT_ASC,
        'gto_valid_from'          => SORT_ASC,
        'gto_round_description'   => SORT_ASC,
        'forgroup'                => SORT_ASC,
    );

    protected int $_completed       = 0;
    protected int $_open            = 0;
    protected int $_missed          = 0;
    protected int $_future          = 0;
    protected int $_completedTrack  = 0;
    protected int $_openTrack       = 0;
    protected int $_missedTrack     = 0;
    protected int $_futureTrack     = 0;

    /**
     * The display format for the date
     *
     * @var string
     */
    protected string $_dateFormat;

    /**
     * The display format for the date/time fields
     *
     * @var string
     */
    protected string $_dateTimeFormat;

    /**
     * @var string
     */
    protected string $_overviewRoute = 'respondent.overview';

    /**
     * @var string
     */
    protected string $_takeSurveyRoute = 'ask.take';

    /**
     * @var string
     */
    protected string $_tokenEditRoute = 'respondent.tracks.token.edit';

    /**
     * @var string
     */
    protected string $_tokenCorrectRoute = 'respondent.tracks.token.correct';

    /**
     * @var string
     */
    protected string $_tokenPreviewRoute = 'respondent.tracks.token.questions';

    /**
     * @var string
     */
    protected string $_tokenShowRoute = 'respondent.tracks.token.show';

    /**
     * @var string
     */
    protected string $_trackAnswerRoute = 'respondent.tracks.token.answer';

    /**
     * @var string
     */
    protected string $_trackDeleteRoute = 'respondent.tracks.delete';

    /**
     * @var string
     */
    protected string $_trackEditRoute = 'respondent.tracks.edit';

    public array $allowedOrgs;

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = false;

    public int $currentOrgId;

    /**
     * Display text for track that can NOT be mailed, set in afterRegistry
     */
    protected ?string $textMailable;

    /**
     * Display text for track that can be mailed, set in afterRegistry
     */
    protected ?string $textNotMailable;

    protected array $tokenParameters = [
        MetaModelInterface::REQUEST_ID => 'gto_id_token',
    ];

    protected array $trackParameters = [
        Model::RESPONDENT_TRACK         => ['gto_id_respondent_track', 'gr2t_id_respondent_track'],
        MetaModelInterface::REQUEST_ID1 => 'gr2o_patient_nr',
        MetaModelInterface::REQUEST_ID2 => 'gr2o_id_organization',
    ];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        MaskRepository $maskRepository,
        MetaModelLoader $metaModelLoader,
        Tracker $tracker,
        TokenRepository $tokenRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly ReceptionCodeRepository $receptionCodeRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $currentUserRepository, $maskRepository, $metaModelLoader, $tracker, $tokenRepository);

        // Load the display dateformat
        $dateType     = new GemsDateType($this->translate);
        $dateTimeType = new GemsDateTimeType($this->translate);
        $this->_dateFormat     = $dateType->dateFormat;
        $this->_dateTimeFormat = $dateTimeType->dateFormat;

        // Initialize the tooltips
        $this->textNotMailable = $this->_("May not be mailed");
        $this->textMailable    = $this->_("May be mailed");

        // Initialize lookup for allowed and current organization
        $this->allowedOrgs  = $this->currentUser->getAllowedOrganizations();
        $this->currentOrgId = $this->currentUser->getCurrentOrganizationId();
    }

    protected function _addTooltip(HtmlElement $element, $text, $placement = "auto")
    {
        $element->setAttrib('data-toggle', 'tooltip')
                ->setAttrib('data-placement', $placement)
                ->setAttrib('data-html', true) // For multiline tooltips
                ->setAttrib('title', $text);
    }

    public function _extractParameters(array $data, array $keys)
    {
        $output = [];

        foreach ($keys as $param => $input) {
            if (is_array($input)) {
                foreach ($input as $subKey) {
                    if (isset($data[$subKey])) {
                        $output[$param] = $data[$subKey];
                        break;
                    }
                }
                if ((! isset($output[$param])) && isset($data[$param]))  {
                    $output[$param] = $data[$param];
                }
            } elseif (isset($data[$input])) {
                $output[$param] = $data[$input];
            } elseif (isset($data[$param])) {
                $output[$param] = $data[$param];
            }
        }

        return $output;
    }

    protected function _getDeleteIcon($row, $trackParameterSource, $isSuccess = true)
    {
        $deleteTrackContainer = Html::create('div', array('class' => 'otherOrg pull-right', 'renderClosingTag' => true));
        if ($row['gr2o_id_organization'] != $this->currentOrgId) {
            $deleteTrackContainer[] = $this->organizationRepository->getOrganization($row['gr2o_id_organization'])->getName() . ' ';
        }
        if (array_key_exists($row['gr2o_id_organization'], $this->allowedOrgs)) {
            if ($isSuccess) {
                $caption = $this->_("Delete track!");
                $icon    = 'trash';
            } else {
                $caption = $this->_("Undelete track!");
                $icon    = 'recycle';
            }
            $deleteLabel = Html::create('i', array(
                'class'            => 'fa fa-' . $icon . ' deleteIcon',
                'renderClosingTag' => true
            ));
            $this->_addTooltip($deleteLabel, $caption, 'left');

            $link = $this->createTrackLink($this->_trackDeleteRoute, $trackParameterSource, $deleteLabel);
            $deleteTrackContainer[] = $link;
        }

        return $deleteTrackContainer;
    }

    protected function _getEditIcon($row, $trackParameterSource)
    {
        if (array_key_exists($row['gr2o_id_organization'], $this->allowedOrgs)) {
            $editLabel = Html::create('i', array(
                'class'            => 'fa fa-pencil',
                'renderClosingTag' => true
            ));
            $this->_addTooltip($editLabel, $this->_("Edit track"), 'right');

            $link = $this->createTrackLink($this->_trackEditRoute, $trackParameterSource, $editLabel);
        } else {
            // When org not allowed, dont add the link, so the track will just open
            $link = Html::create('span', array('class' => 'fa fa-pencil', 'renderClosingTag' => true));
        }
        return $link;
    }

    protected function _getMailIcon($row)
    {
        if (!array_key_exists('gr2t_mailable', $row)) {
            return null;
        }

        $tooltipText    = $row['gr2t_mailable'] == 0 ? $this->textNotMailable : $this->textMailable;
        $icon           = Html::create('i', array('class' => 'fa fa-envelope-o fa-fw', 'renderClosingTag' => true));
        $mailableIcon   = array();
        $mailableIcon[] = $icon;

        if ($row['gr2t_mailable'] == 0) {
            $icon           = Html::create('i', array('class' => 'fa fa-close fa-fw icon-danger', 'renderClosingTag' => true));
            $mailableIcon[] = $icon;
        }
        $this->_addTooltip($icon, $tooltipText, 'right');

        return $mailableIcon;
    }

    /**
     * Initialize the view
     *
     * Make sure the needed javascript is loaded
     *
     * @param \Zend_View $view
     * /
    protected function _initView($view)
    {
        $baseUrl = $this->basepath->getBasePath();

        // Make sure we can use jQuery
        \MUtil\JQuery::enableView($view);

        // Now add the scrollTo plugin so we can scroll to today
        $view->headScript()->appendFile($baseUrl . '/gems/js/jquery.scrollTo.min.js');
        $view->headScript()->appendFile($baseUrl . '/gems/js/gems.copyToClipboard.js');
        
        /*
         * And add some initialization:
         *  - Hide all tokens initially (accessability, when no javascript they should be visible)
         *  - If there is a day labeled today, scroll to it (prevents errors when not visible)
         * /
        $view->headScript()->appendFile($baseUrl . '/gems/js/gems.trafficlight.js');
        $view->headScript()->appendFile($baseUrl . '/gems/js/gems.verticalExpand.js');
        $view->headScript()->appendFile($baseUrl . '/gems/js/gems.respondentAnswersModal.js');
    } // */

    /**
     * Copied from \Gems_Token, to save overhead of loading a token just for this check
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isCompleted($tokenData)
    {
        return ($tokenData['token_status'] == "A");
    }

    /**
     * Are we past the valid until date
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isMissed($tokenData)
    {
        return in_array($tokenData['token_status'], ['M', 'I']);
    }

    /**
     * Are we past the valid from date but before the valid until date?
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isValid($tokenData)
    {
        return in_array($tokenData['token_status'], ['O', 'P']);
    }

    protected function _loadData()
    {
        $model = $this->getModel();
        $metaModel = $model->getMetaModel();
        $metaModel->trackUsage();

        $items = array(
            'gto_id_respondent_track',
            'gto_valid_from',
            'gto_valid_until',
            'gr2t_start_date',
            'gtr_track_name',
            'gr2t_track_info',
            'gto_id_token',
            'gto_round_description',
            'forgroup',
            'gsu_survey_name',
            'gto_completion_time',
            'gr2o_patient_nr',
            'gr2o_id_organization',
            'gro_icon_file',
            'gto_icon_file',
            'gr2t_mailable',        // For mail icon
            'gr2t_reception_code',  // For deleted tracks
            'gr2t_comment',         // For deleted tracks
            'gto_result',
            'ggp_member_type' // For edit vs take (respondent / staff)
        );
        foreach ($items as $item)
        {
            $metaModel->get($item);
        }

        return $model->load($this->getFilter($metaModel), $this->_fixedSort);
    }

    public function addToken(array $tokenData)
    {
        // We add all data we need so no database calls needed to load the token
        $tokenDiv = Html::create('div', ['class' => 'zpitem', 'renderClosingTag' => true]);
        $innerDiv = $tokenDiv->div(array('class' => 'tokenwrapper', 'renderClosingTag' => true));

        $toolsDiv = Html::create('div', ['class' => 'tools', 'renderClosingTag' => true]);
        $innerDiv[] = $toolsDiv;

        $this->getToolIcons($toolsDiv, $tokenData);
        $this->addTokenIcon($toolsDiv, $tokenData);

        $tokenClass = $this->tokenRepository->getStatusClass($tokenData['token_status']);

        $tokenLink = null;
        $tooltip   = [];

        switch ($tokenData['token_status']) {
            case 'A': // Answered
                $tokenLink = $this->createTokenLink($this->_trackAnswerRoute, $tokenData);
                $tooltip = array(sprintf($this->_('Completed') . ': %s', $tokenData['gto_completion_time']->format($this->_dateTimeFormat)));
                if (!empty($tokenData['gto_result'])) {
                    $tooltip[] = Html::raw('<br/>');
                    $tooltip[] = sprintf($this->_('Result') .': %s', $tokenData['gto_result']);
                }
                $this->_completed++;
                if ($tokenLink) {
                    $tokenLink->appendAttrib('class', 'inline-answers');
                }
                break;

            case 'O': // Open
            case 'P': // Partial
                $tokenLink = $this->createTokenLink($this->_takeSurveyRoute, $tokenData);
                if ($tokenData['ggp_member_type'] == 'respondent') {
                    $tokenLink = $this->createTokenLink($this->_tokenShowRoute, $tokenData);
                }
                if (is_null($tokenData['gto_valid_until'])) {
                    $tooltip = $this->_('Does not expire');
                } else {
                    $tooltip = sprintf($this->_('Open until %s'), $tokenData['gto_valid_until']->format($this->_dateTimeFormat));
                }
                $this->_open++;
                break;

            case 'M': // Missed
            case 'I': // Incomplete
                $tokenLink = $this->createTokenLink($this->_tokenEditRoute, $tokenData);
                $tooltip = sprintf($this->_('Missed since %s'), $tokenData['gto_valid_until']->format($this->_dateTimeFormat));
                $this->_missed++;
                break;

            case 'W': //Waiting
                $tokenLink = $this->createTokenLink($this->_tokenEditRoute, $tokenData);
                $tooltip = sprintf($this->_('Valid from %s'), $tokenData['gto_valid_from']->format($this->_dateTimeFormat));
                $this->_future++;

            default:
                break;
        }

        if (empty($tokenLink)) {
            $tokenClass .= ' disabled';
            $tokenLink = Html::create('div', ['class'=>'disabled']);
        }
        $tokenDiv->appendAttrib('class', $tokenClass);
        $innerDiv[] = $tokenLink;

        $this->_addTooltip($tokenLink, $tooltip, 'auto top');
        $tokenLink[] = $tokenData['gsu_survey_name'];

        return $tokenDiv;
    }

    protected function addTokenIcon($toolsDiv, $tokenData)
    {
        $iconFile = '';
        if (!empty($tokenData['gto_icon_file'])) {
            $iconFile = $tokenData['gto_icon_file'];
        } elseif (!empty($tokenData['gro_icon_file'])) {
            $iconFile = $tokenData['gro_icon_file'];
        }
        if (!empty($iconFile)) {
            $toolsDiv->img(array('src' => $tokenData['gto_icon_file'], 'class' => 'icon'));
        }
    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    public function createModel(): DataReaderInterface
    {
        $model = parent::createModel();

        if ($model instanceof JoinModel && !$model->getMetaModel()->has('forgroup')) {
            $model->addColumn('gems__groups.ggp_name', 'forgroup');
        }

        return $model;
    }

    public function createRouteLink(string $route, array $row = [], mixed $label = null): ?HtmlElement
    {
        $menuUrl = $this->menuHelper->getRouteUrlArray($route, $row + $this->requestInfo->getRequestMatchedParams());

        if (is_array($menuUrl)) {
            if ($label) {
                if (is_string($label)) {
                    $label = strtolower($label);
                }
            } else {
                $label = strtolower($menuUrl['label']);
            }
            return Html::create('actionLink', $menuUrl['url'], $label);
        }
        return null;
    }

    public function createTokenLink(string $route, array $row = [], mixed $label = null): ?HtmlElement
    {
        return $this->createRouteLink($route, $this->_extractParameters($row, $this->tokenParameters + $this->trackParameters), $label);
    }

    public function createTrackLink(string $route, array $row = [], mixed $label = null): ?HtmlElement
    {
        return $this->createRouteLink($route, $this->_extractParameters($row, $this->trackParameters), $label);
    }

    protected function finishGroup($progressDiv)
    {
        if (is_null($progressDiv)) {
            return;
        }

        if ($this->_missed > 0) {
            $progressDiv->div($this->_missed, array('class' => 'missed'));
        }
        if ($this->_completed > 0) {
            $progressDiv->div($this->_completed, array('class' => 'answered'));
        }
        if ($this->_open > 0) {
            $progressDiv->div($this->_open, array('class' => 'open'));
        }
        if ($this->_future > 0) {
            $progressDiv->div($this->_future, array('class' => 'waiting'));
        }

        $this->_missedTrack    = $this->_missedTrack + $this->_missed;
        $this->_completedTrack = $this->_completedTrack + $this->_completed;
        $this->_openTrack      = $this->_openTrack + $this->_open;
        $this->_futureTrack    = $this->_futureTrack + $this->_future;

        $this->_completed = 0;
        $this->_missed    = 0;
        $this->_open      = 0;
        $this->_future    = 0;
    }

    protected function finishTrack($progressDiv)
    {
        if (!is_null($progressDiv)) {
            $total = max($this->_completedTrack + $this->_openTrack + $this->_missedTrack + $this->_futureTrack, 1);

            $div = $progressDiv->div($this->_missedTrack, array(
                'class'            => 'progress-bar missed',
                'style'            => 'width: ' . $this->_missedTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s missed"), $this->_missedTrack) , 'top');
            $div = $progressDiv->div($this->_completedTrack, array(
                'class'            => 'progress-bar answered',
                'style'            => 'width: ' . $this->_completedTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s completed"), $this->_completedTrack), 'top');
            $div = $progressDiv->div($this->_openTrack, array(
                'class'            => 'progress-bar open',
                'style'            => 'width: ' . $this->_openTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s open"), $this->_openTrack), 'top');
            $div = $progressDiv->div($this->_futureTrack, array(
                'class'            => 'progress-bar waiting',
                'style'            => 'width: ' . $this->_futureTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s upcoming"), $this->_futureTrack), 'top');
        }

        $this->_missedTrack    = 0;
        $this->_completedTrack = 0;
        $this->_openTrack      = 0;
        $this->_futureTrack    = 0;

        return;
    }

    public function getFilter(MetaModelInterface $metaModel): array
    {
        $filter['gto_id_respondent']   = $this->respondent->getId();
        if (is_array($this->forOtherOrgs)) {
            $filter['gto_id_organization'] = $this->forOtherOrgs;
        } elseif (true !== $this->forOtherOrgs) {
            $filter['gto_id_organization'] = $this->respondent->getOrganizationId();
        }

        // Filter for valid track reception codes
        $filter[] = 'gr2t_reception_code IN (SELECT grc_id_reception_code FROM gems__reception_codes WHERE grc_success = 1) OR (gto_completion_time IS NOT NULL)';
        $filter['grc_success'] = 1;
        // Active round
        // or
        // no round
        // or
        // token is success and completed
        $filter[] = 'gro_active = 1 OR gro_active IS NULL OR (gto_completion_time IS NOT NULL)';
        $filter['gsu_active']  = 1;

        return $filter;
    }

    /**
     * @inheritdoc
     */
    public function getHtmlOutput()
    {
        $mainDiv = Html::create('div', ['class' => 'panel panel-default', 'id' => 'trackwrapper', 'renderClosingTag' => true]);

        $currentTrackId  = null; //\Gems\Cookies::get($this->request, 'track_idx');
        $data            = $this->_loadData();
        $doelgroep       = null;
        $lastDate        = null;
        $lastDescription = null;
        $now             = time();
        $progressDiv     = null;
        $respTrackId     = 0;
        $today           = time();
        $trackProgress   = null;
        $minIcon         = Html::create('span', array('class' => 'fa fa-plus-square', 'renderClosingTag' => true));
        $summaryIcon     = Html::create('i', array('class' => 'fa fa-list-alt fa-fw', 'renderClosingTag' => true));
        $trackIds        = array_column($data, 'gto_id_respondent_track', 'gto_id_respondent_track');

        // Check for cookie set for this patient
        if (! isset($trackIds[$currentTrackId])) {
            $currentTrackId =  reset($trackIds);
        }
        
        // The normal loop
        foreach ($data as $row)
        {
            if ($respTrackId !== $row['gto_id_respondent_track']) {
                if (isset($dateDiv) && $lastDate instanceof \DateTimeInterface && $lastDate->getTimestamp() <= $now) {
                    $dateDiv->appendAttrib('class', 'today');
					unset($dateDiv);
                }
                $progressDiv = $this->finishGroup($progressDiv);
                $this->finishTrack($trackProgress);

                $doelgroep            = null;
                $lastDate             = null;
                $lastDescription      = null;
                $respTrackId          = $row['gto_id_respondent_track'];
                $trackParameterSource = array(
                    Model::RESPONDENT_TRACK         => $row['gto_id_respondent_track'],
                    MetaModelInterface::REQUEST_ID1 => $row['gr2o_patient_nr'],
                    MetaModelInterface::REQUEST_ID2 => $row['gr2o_id_organization'],
                );

                if ($row['gto_id_respondent_track'] == $currentTrackId) {
                    $caretClass = "fa-chevron-down";
                    $bodyStyle  = "";  
                } else {
                    $caretClass = "fa-chevron-right";
                    $bodyStyle  = "display: none;";
                }
                
                $trackDiv         = $mainDiv->div(array('class' => 'panel panel-default traject verticalExpand'));
                $trackHeading     = $trackDiv->div(array('class' => 'panel-heading header', 'renderClosingTag' => true));

                $trackTitle    = Html::create('span', array('class' => 'title'));
                $trackTitle[]  = ' ' . $row['gtr_track_name'];
                $trackTitle[]  = Html::create('span', array('class' => "header-caret fa fa-fw " . $caretClass, 'renderClosingTag' => true));

                $trackReceptionCode = $this->receptionCodeRepository->getReceptionCode($row['gr2t_reception_code']);
                if (!$trackReceptionCode->isSuccess()) {
                    $trackDiv->appendAttrib('class', 'deleted');
                    $description = $trackReceptionCode->getDescription();
                    if (!empty($row['gr2t_comment'])) {
                        $description .= sprintf(' (%s)', $row['gr2t_comment']);
                    }
                    $trackTitle[] = Html::create('div', $description, array('class'=>'description'));
                }

                $trackHeader   = $trackHeading->h3(array('class' => "panel-title", 'renderClosingTag' => true));
                $trackHeader[] = $this->_getEditIcon($row, $trackParameterSource);
                $trackHeader[] = $this->_getMailIcon($row);
                $trackHeader[] = $trackTitle;
                $trackHeader[] = $this->_getDeleteIcon($row, $trackParameterSource, $trackReceptionCode->isSuccess());

                if ($row['gr2t_start_date'] instanceof \DateTimeInterface) {
                    $trackStartDate = $row['gr2t_start_date']->format($this->_dateFormat);
                } else {
                    $trackStartDate = $this->_('n/a');
                }
                $trackHeading->div($row['gr2t_track_info'], array('renderClosingTag' => true));
                $trackHeading->div($this->_('Start date') . ': ' . $trackStartDate);
                $trackProgress = $trackHeading->div(array('class' => 'progress pull-right', 'renderClosingTag' => true));

                $container    = $trackDiv->div(array('class' => 'panel-body', 'style' => $bodyStyle));
                $subcontainer = $container->div(array('class' => 'objecten', 'renderClosingTag' => true));
            } else {
                $subcontainer = Html::create('dummy');
            }

            $date = $row['gto_valid_from'];
            if (! $date instanceof \DateTimeInterface) {
                $date = $date ? \DateTimeImmutable::createFromFormat($this->_dateFormat, $date) : null;
                if (! $date instanceof \DateTimeInterface) {
                    continue;
                }
            }

            $description = $row['gto_round_description'];
            if (is_null($description)) $description = '';
            if ($lastDescription !== $description || !isset($dateDiv)) {
                if (isset($dateDiv) && $lastDate instanceof \DateTimeInterface && $lastDate->getTimestamp() < $now && $row['gto_valid_from']->getTimestamp() > $now) {
                    $dateDiv->appendAttrib('class', 'today');
                }
                $lastDescription = $description;
                $progressDiv     = $this->finishGroup($progressDiv);
                $lastDate        = $date;
                $class           = 'object';
                if ($date->getTimestamp() == $today) {
                    $class .= ' today';
                }

                $dateDiv = $subcontainer->div(array('class' => $class, 'renderClosingTag' => true));
                $this->_addTooltip($summaryIcon, $this->_('Summary'), 'auto top');
                $params = [
                    'gto_id_respondent_track' => $row['gto_id_respondent_track'],
                    'gto_round_description' => urlencode(str_replace('/', '&#47;', $description)),
                    'gr2o_patient_nr' => $row['gr2o_patient_nr'],
                    'gr2o_id_organization' => $row['gr2o_id_organization'],
                ];
                $summaryLink = $this->createTrackLink($this->_overviewRoute, $params, $summaryIcon);
                if (!$summaryLink) {
                    $summaryLink = Html::create('div', $summaryIcon, array('renderClosingTag' => true));
                }
                $summaryLink->appendAttrib('class', 'pull-right inline-answers');
                $dateDiv->h5(array($summaryLink, ucfirst($description)));
                $dateDiv->h6($date->format($this->_dateFormat));

                $doelgroep = null;

            } elseif ($lastDate !== $date) {
                // When we have a new start date, add the date and start a new group
                $dateDiv->h6($date->format($this->_dateFormat));
                $lastDate = $date;
                $doelgroep = null;
            }

            if ($doelgroep !== $row['forgroup']) {
                $this->finishGroup($progressDiv);
                $doelgroep    = $row['forgroup'];
                $doelgroepDiv = $dateDiv->div(array('class' => 'actor', 'renderClosingTag' => true));
                $doelgroepDiv->h6(array($minIcon, $doelgroep));
                $progressDiv  = $doelgroepDiv->div(array('class' => 'zplegenda', 'renderClosingTag' => true));
                $tokenDiv     = $doelgroepDiv->div(array('class' => 'zpitems', 'renderClosingTag' => true));
            }

            $tokenDiv[] = $this->addToken($row);
        }
        if (isset($dateDiv) && $lastDate->getTimestamp() < $now) {
            $dateDiv->appendAttrib('class', 'today');
        }
        $progressDiv = $this->finishGroup($progressDiv);
        $this->finishTrack($trackProgress);

        return $mainDiv;
    }

    /**
     * @param HtmlElement $toolsDiv
     * @param array $token
     * @return void
     */
    public function getToolIcons(HtmlElement $toolsDiv, array $token)
    {
        static $correctIcon;
        static $showIcon;
        static $clipboardIcon;

        if (!isset($correctIcon)) {
            $correctIcon = Html::create('i', array(
                    'class'            => 'fa fa-fw fa-pencil dropdown-toggle',
                    'renderClosingTag' => true
                ));
        }

        if (!isset($showIcon)) {
            $showIcon = Html::create('i', array(
                    'class'            => 'fa fa-fw fa-ellipsis-h dropdown-toggle',
                    'renderClosingTag' => true
                ));
        }

        if (!isset($clipboardIcon)) {
            $clipboardIcon = Html::create('i', array(
                'class'            => 'fa fa-fw fa-clipboard dropdown-toggle',
                'renderClosingTag' => true
            ));
        }
        
        // When not completed we have no correct
        if ($this->_isCompleted($token)) {
            $correctLink = $this->createTokenLink($this->_tokenCorrectRoute, $token, $correctIcon);
            if ($correctLink) {
                $dropUp = $toolsDiv->div(array('class' => 'dropdown dropup pull-right', 'renderClosingTag' => true));
                $this->_addTooltip($dropUp, $this->menuHelper->getRouteMenuLabel($this->_tokenCorrectRoute));
                $dropUp->append($correctLink);
            }
        }

        $showLink = $this->createTokenLink($this->_tokenShowRoute, $token, $showIcon);
        if ($showLink) {
            $dropUp = $toolsDiv->div(array('class' => 'dropdown dropup pull-right', 'renderClosingTag' => true));
            $this->_addTooltip($dropUp, $this->_('Details'));
            $dropUp->append($showLink);
        }
        
        // When not completed and not missed (meaning open or future) we can copy the token
        if (!$this->_isCompleted($token) && !$this->_isMissed($token)) {
            // We now use the copy icon to allow copy token to clipboard
            $dropUp = $toolsDiv->div(array(
                                         'class' => 'dropdown dropup pull-right clipboard copier-to-clipboard',
                                         'data-clipboard-text' => $token['gto_id_token'],
                                         'data-clipboard-after' => sprintf($this->_('Copied: %s'), $token['gto_id_token']),
                                         'data-toggle' => 'tooltip',
                                         'title' => $this->_('Copy'),
                                         'renderClosingTag' => true
                                     ));
            $dropUp->append($clipboardIcon);
        }
    }
}
