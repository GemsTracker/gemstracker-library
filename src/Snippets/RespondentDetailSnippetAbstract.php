<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\ConsentRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use Gems\User\User;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\AElement;
use Zalt\Late\Late;
use Zalt\Message\MessageTrait;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Prepares displays of respondent information
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class RespondentDetailSnippetAbstract extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    use MessageTrait;

    protected User $currentUser;

    /**
     *
     * @var \Gems\Model\Respondent\RespondentModel
     */
    protected $model;

    /**
     * Optional: repeater respondent data
     *
     * @var \Zalt\Late\RepeatableInterface
     */
    protected $repeater;

    /**
     * Optional
     *
     * @var ?\Gems\Tracker\Respondent
     */
    protected ?Respondent $respondent = null;

    /**
     * Show a warning if informed consent has not been set
     *
     * @var boolean
     */
    protected $showConsentWarning = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        MessengerInterface $messenger,
        protected readonly ConsentRepository $consentRepository,
        protected readonly MaskRepository $maskRepository,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly OrganizationRepository $organizationRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->messenger = $messenger;
    }

    /**
     * Place to set the data to display
     *
     * @param DetailTableBridge $bridge
     * @return void
     */
    abstract protected function addTableCells(DetailTableBridge $bridge);

    /**
     * Check if we have the 'Unknown' consent, and present a warning. The project default consent is
     * normally 'Unknown' but this can be overruled in project.ini so checking for default is not right
     *
     * @static boolean $warned We need only one warning in case of multiple consents
     * @param string $consent
     */
    public function checkConsent($consent)
    {
        static $warned;

        if ($warned) {
            return $consent;
        }

        $unknown = $this->consentRepository->getConsentUnknown();

        // Value is translated by now if in bridge
        if (($consent == $unknown) || ($consent == $this->_($unknown))) {

            $warned    = true;
            $msg       = $this->_('Please settle the informed consent form for this respondent.');

//            if ($this->menuSnippetHelper) {
//                $queryParams = $this->requestInfo->getParams();
//
//                $url = $this->menuSnippetHelper->getRouteUrl('respondent.change-consent', $queryParams);
//                if (! $url) {
//                    $url = $this->menuSnippetHelper->getRouteUrl('respondent.edit', $queryParams);
//                    if ($url) {
//                        $url .= '#tabContainer-frag-4';
//                    }
//                }
//            }
//            if ($url) {
//                $this->addMessage(Html::create()->a($url, $msg)->render());
//            } else {
                $this->addMessage($msg);
//            }
        }

        return $consent;
    }

    /**
     * Returns the caption for this table
     *
     * @param boolean $onlyNotCurrent Only return a string when the organization is different
     * @return string
     */
    protected function getCaption(bool $onlyNotCurrent = false): string
    {
        $orgId = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID2);
        if ($orgId == $this->currentUser->getCurrentOrganizationId()) {
            if ($onlyNotCurrent) {
                return '';
            }
            return $this->_('Respondent information');
        }
        return sprintf($this->_('%s respondent information'), $this->organizationRepository->getOrganization($orgId)->getName());
    }

    public function getHtmlOutput()
    {
        /**
         * @var DetailTableBridge $bridge
         */
        $bridge = $this->model->getBridgeFor('itemTable', array('class' => 'displayer table table-condensed'));
        $bridge->setRepeater($this->repeater);
        $bridge->setColumnCount(2); // May be overruled

        $this->addTableCells($bridge);

        if ($this->model->getMetaModel()->has('row_class')) {
            // Make sure deactivated rounds are show as deleted
            foreach ($bridge->getTable()->tbody() as $tr) {
                foreach ($tr as $td) {
                    if ('td' === $td->tagName) {
                        // @phpstan-ignore-next-line
                        $td->appendAttrib('class', $bridge->row_class);
                    }
                }
            }
        }

        $container = Html::create()->div(array('class' => 'table-container'));
        $container[] = $bridge->getTable();
        return $container;
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
        $metaModel = $this->model->getMetaModel();
        $metaModel->setIfExists('gr2o_email', 'itemDisplay', [AElement::class, 'ifmail']);
        $metaModel->setIfExists('gr2o_comments', 'rowspan', 2);

        if ($this->showConsentWarning && $metaModel->has('gr2o_consent')) {
            $metaModel->set('gr2o_consent', 'formatFunction', array($this, 'checkConsent'));
        }

        if (! $this->repeater) {
            if ($this->respondent) {
                $data = [$this->respondent->getArrayCopy()];
                $this->repeater = Late::repeat($data);
            } else {
                $keys   = $metaModel->getKeys();
                $filter = [];
                foreach ($keys as $id => $name) {
                    $val = $this->requestInfo->getParam($id);
                    $filter[$name] = $val;
                }

                $this->repeater = $this->model->loadRepeatable($filter);
            }
        }

        return true;
    }
}
