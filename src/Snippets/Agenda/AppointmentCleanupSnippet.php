<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\UrlArrayAttribute;
use Zalt\Message\MessageTrait;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-mrt-2015 11:11:12
 */
class AppointmentCleanupSnippet extends \Gems\Snippets\ModelDetailTableSnippet
{
    use MessageTrait;

    /**
     * The action to go to when the user clicks 'No'.
     *
     * If you want to change to another controller you'll have to code it.
     *
     * @var string
     */
    protected $abortRoute = 'show';

    /**
     * @var ? string Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    protected ?string $afterSaveRouteUrl = null;

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_SINGLE_ROW;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    /**
     * The action to go to when the user clicks 'Yes' and the data is deleted.
     *
     * If you want to change to another controller you'll have to code it.
     *
     * @var string
     */
    protected $cleanupRoute = 'show';

    /**
     * The request parameter used to store the confirmation
     *
     * @var string Required
     */
    protected $confirmParameter = 'confirmed';

    /**
     *
     * @var mixed the field or fields in appointments linking to this appointment
     */
    protected mixed $filterOn = null;

    /**
     *
     * @var ?string The field in the model containing the yes/no filter
     */
    protected ?string $filterWhen = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly ResultFetcher $resultFetcher,
        )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        $this->messenger = $messenger;
    }

    /**
     * When hasHtmlOutput() is false a snippet user should check
     * for a redirectRoute.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute(): ?string
    {
        return $this->afterSaveRouteUrl;
    }

    /**
     * Get the appointment where for this snippet
     *
     * @return string
     */
    protected function getWhere()
    {
        $id = intval($this->requestInfo->getParam(MetaModelInterface::REQUEST_ID));
        $add = " = " . $id;

        return implode($add . ' OR ', (array) $this->filterOn) . $add;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if ($this->bridgeMode === BridgeInterface::MODE_SINGLE_ROW) {
            $bridge = $this->getModel()->getBridgeFor($this->bridgeClass);
            $row = $bridge->getRow();
            if (empty($row)) {
                $this->setNotFound();
                return false;
            }
        }
        if ($this->requestInfo->getParam($this->confirmParameter)) {
            $this->performAction();

            $redirectRoute = $this->getRedirectRoute();
            return empty($redirectRoute);

        } else {
            return parent::hasHtmlOutput();
        }
    }

    /**
     * Overrule this function if you want to perform a different
     * action than deleting when the user choose 'yes'.
     */
    protected function performAction()
    {
        $count = $this->resultFetcher->deleteFromTable("gems__appointments", $this->getWhere());

        $this->addMessage(sprintf($this->plural(
                '%d appointment deleted.',
                '%d appointments deleted.',
                $count
                ), $count));


        $this->setAfterCleanupRoute();
    }

    /**
     * Set what to do when the form is 'finished'.
     */
    protected function setAfterCleanupRoute()
    {
        if ($this->cleanupRoute) {
            $this->afterSaveRouteUrl = $this->menuSnippetHelper->getRelatedRouteUrl($this->cleanupRoute);
        }
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param DetailTableBridge $bridge
     * @param DataReaderInterface $model
     * @return void
     */
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $model)
    {
        $fparams = array('class' => 'centerAlign');
        $row     = $bridge->getRow();

        if (isset($this->filterOn, $this->filterWhen, $row[$this->filterWhen]) && $row[$this->filterWhen]) {
            $count = $this->resultFetcher->fetchOne("SELECT COUNT(*) FROM gems__appointments WHERE " . $this->getWhere());

            if ($count) {
                $footer = $bridge->tfrow($fparams);

                $footer[] = sprintf($this->plural(
                        'This will delete %d appointment. Are you sure?',
                        'This will delete %d appointments. Are you sure?',
                        $count
                        ), $count);
                $footer[] = ' ';
                $footer->actionLink(
                        UrlArrayAttribute::toUrlString([$this->menuSnippetHelper->getCurrentUrl()] + [$this->confirmParameter => 1]),
                        $this->_('Yes')
                        );
                $footer[] = ' ';

                $footer->actionLink(
                    $this->menuSnippetHelper->getRelatedRouteUrl($this->abortRoute),
                    $this->_('No')
                    );

            } else {
                $this->addMessage($this->_('Clean up not needed!'));
                $bridge->tfrow($this->_('No clean up needed, no appointments exist.'), $fparams);
            }
        } else {
            $this->addMessage($this->_('Clean up filter disabled!'));
            $bridge->tfrow($this->_('No clean up possible.'), array('class' => 'centerAlign'));
        }
    }
}
