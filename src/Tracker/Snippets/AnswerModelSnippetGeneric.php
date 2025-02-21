<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Gems\Event\Application\AnswerFilterEvent;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\TokenRepository;
use Gems\Snippets\ModelTableSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\SurveyModel;
use Gems\User\Mask\MaskRepository;
use Gems\User\User;
use Laminas\Db\Sql\Expression;
use MUtil\Model;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Html\HtmlElement;
use Zalt\Late\Late;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Displays answers to a survey.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class AnswerModelSnippetGeneric extends ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('grc_success' => SORT_DESC, 'gto_round_order' => SORT_ASC, 'gto_valid_from' => SORT_ASC, 'gto_completion_time' => SORT_ASC);

    /**
     * Empty or a \Gems\Tracker\Snippets\AnswerNameFilterInterface object that is
     * used to filter the answers that are displayed.
     *
     * @var \Gems\Tracker\Snippets\AnswerNameFilterInterface
     */
    protected $answerFilter;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'answer answers browser table compliance copy-to-clipboard-before';


    protected User $currentUser;

    /**
     *
     * @var string Format used for displaying dates.
     */
    protected $dateFormat = 'j M Y';

    /**
     * Switch to put the display of the cancel and print buttons.
     *
     * @var boolean
     */
    protected $showButtons = true;

    /**
     * Switch to enable/disable the 'take' button underneath each
     * open token.
     *
     * @var boolean
     */
    protected $showTakeButton = true;

    /**
     * Switch to put the display of the headers on or off
     *
     * @var boolean
     */
    protected $showHeaders = true;

    /**
     * Switch to put the display of the current token as select to true or false.
     *
     * @var boolean
     */
    protected $showSelected = true;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var \Gems\Tracker\Token
     */
    protected $token;

    /**
     * Required: id of the selected token to show
     *
     * @var string
     */
    protected $tokenId;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected EventDispatcherInterface $event,
        protected Locale $locale,
        protected MaskRepository $maskRepository,
        protected Tracker $tracker,
        protected TokenRepository $tokenRepository,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $br = Html::create('br');
        if ($this->showSelected) {
            $selectedClass = Late::iff(Late::comp($bridge->gto_id_token, '==', $this->tokenId), 'selectedColumn', null);
        } else {
            $selectedClass = null;
        }

        $bridge->th($this->_('Status'));
        $td = $bridge->tdh([
            $this->tokenRepository->getTokenStatusLinkForBridge($bridge, $this->menuHelper),
            ' ',
            Late::first($bridge->grc_description, $this->_('OK')),
            ]);
        $td->appendAttrib('class', $selectedClass);

        $bridge->th($this->_('Question'));
        $metaModel = $dataModel->getMetaModel();
        if ($metaModel->has('grr_name') && $metaModel->has('gtf_field_name')) {
            $td = $bridge->tdh(
                    Late::iif($bridge->grr_name, array($bridge->grr_name, $br)),
                    Late::iif($bridge->gtf_field_name, array($bridge->gtf_field_name, $br)),
                    $bridge->gto_round_description,
                    Late::iif($bridge->gto_round_description, $br),
                    Late::iif($bridge->gto_completion_time, $bridge->gto_completion_time, $bridge->gto_valid_from)
                    );
        } else {
            $td = $bridge->tdh(
                    $bridge->gto_round_description,
                    Late::iif($bridge->gto_round_description, $br),
                    Late::iif($bridge->gto_completion_time, $bridge->gto_completion_time, $bridge->gto_valid_from)
                    );
        }
        $td->appendAttrib('class', $selectedClass);
        $td->appendAttrib('class', $bridge->row_class);

        // Apply filter on the answers displayed
        $answerNames = $metaModel->getItemsOrdered();

        $eventName = 'gems.survey.answers.display-filter';

        if ($this->answerFilter instanceof AnswerNameFilterInterface) {
            $answerFilter = $this->answerFilter;
            $eventFunction = function (AnswerFilterEvent $event) use ($answerFilter) {
                $bridge = $event->getBridge();
                $model = $event->getModel();
                $answerNames = $event->getCurrentNames();

                $answerNames = $answerFilter->filterAnswers($bridge, $model, $answerNames, $this->requestInfo);

                $event->setCurrentNames($answerNames);
            };
            $this->event->addListener($eventName, $eventFunction, 100);
        } else {
            $eventFunction = null;
        }

        $answerFilterEvent = new AnswerFilterEvent($bridge, $dataModel, $answerNames);
        $this->event->dispatch($answerFilterEvent, $eventName);
        $answerNames = $answerFilterEvent->getCurrentNames();
        $oldGroup    = null;

        if ($eventFunction) {
            $this->event->removeListener($eventName, $eventFunction, 100);
        }

        $cond    = Html::create('i', ['class' => 'fa fa-code-fork', 'renderClosingTag' => true]);
        $hidden  = Html::create('i', ['class' => 'fa fa-eye-slash', 'renderClosingTag' => true]);
        $visible = Html::create('i', ['class' => 'fa fa-eye', 'renderClosingTag' => true]);
        
        foreach($answerNames as $name) {
            $label = $metaModel->get($name, 'label');
            if (null !== $label) {     // Was strlen($label), but this ruled out empty sub-questions
                $group = $metaModel->get($name, 'groupName');
                if ($oldGroup !== $group) {
                    if ($group) {
                        $bridge->thd(['class' => 'group groupLabel'])->raw($group);
                        $bridge->td($bridge->blank, ['renderClosingTag' => true]);
                    }
                    $oldGroup = $group;
                }
                $th = $bridge->thd(Html::raw($label), array('class' => $metaModel->get($name, 'thClass')));
                $td = $bridge->td($bridge->$name);
                $td->appendAttrib('class', 'answer');
                $td->appendAttrib('class', $selectedClass);
                $td->appendAttrib('class', $bridge->row_class);

                $col2 = $visible;
                if ($metaModel->get($name, 'alwaysHidden')) {
                    $td->appendAttrib('class', 'hideAlwaysQuestion');
                    $td->title = $this->_('Hidden question');
                    $th->title = $this->_('Hidden question');
                    $col2 = $hidden;
                }
                if ($metaModel->get($name, 'hasConditon')) {
                    $td->appendAttrib('class', 'conditionQuestion');
                    $td->title = $this->_('Conditional question');
                    $th->title = $this->_('Conditional question');
                    $col2 = $cond;
                }
                $th->append($col2);
            }
        }

        $bridge->th($this->_('Token'));

        $tokenUpper = $bridge->gto_id_token->strtoupper();

        $url = $this->menuHelper->getLateRouteUrl('ask.take', [
            Model::REQUEST_ID => $bridge->getLate('gto_id_token'),
        ]);

        $td = $bridge->tdh($bridge->can_be_taken->if($url, $tokenUpper));


        /*if ($this->showTakeButton && $menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
            $source = new \Gems\Menu\ParameterSource();
            $source->setTokenId($bridge->gto_id_token);
            $source->offsetSet('can_be_taken', $bridge->can_be_taken);

            $link = $menuItem->toActionLink($source);

            if ($link) {
                $link->title = array($this->_('Token'), $tokenUpper);
            }

            $td = $bridge->tdh($bridge->can_be_taken->if($link, $tokenUpper));
        } else {
            $td = $bridge->tdh($tokenUpper);
        }*/
        $td->appendAttrib('class', $selectedClass);
        $td->appendAttrib('class', $bridge->row_class);
    }

    /**
     * Add the buttons to the result div
     *
     * @param HtmlElement $html
     */
    protected function addButtons(HtmlElement $html)
    {
        $buttonDiv = $html->buttonDiv();
        $buttonDiv->actionLink(array(), $this->_('Close'), array('class' => 'windowCloseButton'));
        $buttonDiv->actionLink(array(), $this->_('Print'), array('class' => 'windowPrintButton'));
    }

    /**
     * Add elements that form the header
     *
     * @param HtmlElement $htmlDiv
     */
    public function addHeaderInfo(HtmlElement $htmlDiv)
    {
        $htmlDiv->h3(sprintf($this->_('%s answers for patient number %s'), $this->token->getSurveyName(), $this->token->getPatientNumber()));

        if (! $this->maskRepository->isFieldMaskedWhole('name')) {
            $htmlDiv->pInfo(sprintf(
                    $this->_('Answers for token %s, patient number %s: %s.'),
                    strtoupper($this->tokenId),
                    $this->token->getPatientNumber(),
                    $this->token->getRespondentName()))
                    ->appendAttrib('class', 'noprint');
        }
    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        /**
         * @var SurveyModel $model
         */
        $model = $this->token->getSurveyAnswerModel($this->locale->getLanguage());

        $rawExpression = $this->tokenRepository->getStatusExpression()->getExpression();

        $model->addColumn(new Expression($rawExpression), 'token_status');
        $model->addColumn(new Expression("' '"), 'blank');

        $metaModel = $model->getMetaModel();
        $metaModel->set('gto_valid_from', [
            'dateFormat' => $this->dateFormat
        ]);
        $metaModel->set('gto_completion_time', [
            'dateFormat' => $this->dateFormat
        ]);

        return $model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     */
    public function getHtmlOutput()
    {
        // $view->headScript()->appendFile($this->basepath->getBasePath() . '/gems/js/gems.copyToClipboard.js');
        
        $htmlDiv = Html::create()->div(array('class' => 'answer-container'));

        if ($this->tokenId) {
            if ($this->token->exists) {
                if ($this->showHeaders) {
                    $this->addHeaderInfo($htmlDiv);
                }

                $table = parent::getHtmlOutput();
                $table->setPivot(true, 2, 1);

                $this->applyHtmlAttributes($table);
                $this->class = false;
                $htmlDiv[] = $table;

            } else {
                $htmlDiv->ul(sprintf($this->_('Token %s not found.'), $this->tokenId), array('class' => 'errors'));
            }

        } else {
            $htmlDiv->ul($this->_('No token specified.'), array('class' => 'errors'));
        }

        if ($this->showButtons) {
            $this->addButtons($htmlDiv);
        }
        return $htmlDiv;
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
        if (! $this->tokenId) {
            if (isset($this->token)) {
                $this->tokenId = $this->token->getTokenId();
            }
        } elseif (! $this->token) {
            $this->token = $this->tracker->getToken($this->tokenId);
        }

        // Output always true, returns an error message as html when anything is wrong
        return true;
    }
}
