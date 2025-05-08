<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Export;

use Gems\Html;
use Gems\Model\Respondent\RespondentModel;
use Gems\Tracker\Respondent;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\Late;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Show info about the respondent during html/pdf export
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class RespondentHeaderSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    protected Respondent $respondent;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly RespondentModel $respondentModel,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }


    public function getHtmlOutput()
    {
        $metaModel      = $this->respondentModel->getMetaModel();
        $respondentData = $this->respondent->getArrayCopy();
        $respondentId   = $this->respondent->getPatientNumber();

        $html = $this->getHtmlSequence();
        if (empty($respondentData)) {
            /** @phpstan-ignore-next-line */
            $html->p()->b(sprintf($this->_('Unknown respondent %s'), $respondentId));
            return $html;
        }

        $bridge = $this->respondentModel->getBridgeFor('itemTable', array('class' => 'browser table copy-to-clipboard-before'));
        $bridge->setRepeater(Late::repeat([$respondentData]));
        /** @phpstan-ignore-next-line */
        $bridge->th($this->_('Respondent information'), array('colspan' => 4));
        /** @phpstan-ignore-next-line */
        $bridge->setColumnCount(2);
        foreach($metaModel->getItemsOrdered() as $name) {
            if ($label = $metaModel->get($name, 'label')) {
                /** @phpstan-ignore-next-line */
                $bridge->addItem($name, $label);
            }
        }

        $tableContainer = Html::create()->div(array('class' => 'table-container'));
        /** @phpstan-ignore-next-line */
        $tableContainer[] = $bridge->getTable();

        $html->h3($this->_('Respondent information') . ': ' . $respondentId);
        $html[] = $tableContainer;
        $html->hr();

        return $html;
    }

}