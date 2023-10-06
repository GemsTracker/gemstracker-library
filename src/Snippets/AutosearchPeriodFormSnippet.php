<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.9.2
 */
class AutosearchPeriodFormSnippet extends AutosearchFormSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        protected PeriodSelectRepository $periodSelectRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuSnippetHelper, $metaModelLoader, $resultFetcher, $messenger);
    }
    
    /**
     * Generate two date selectors and - depending on the number of $dates passed -
     * either a hidden element containing the field name or an radio button or
     * dropdown selector for the type of date to use.
     *
     * @param array $elements Search element array to which the element are added.
     * @param mixed $dates A string fieldName to use or an array of fieldName => Label
     * @param string $defaultDate Optional element, otherwise first is used.
     * @param int $switchToSelect The number of dates where this function should switch to select display
     */
    protected function addPeriodSelectors(array &$elements, $dates, $defaultDate = null, $switchToSelect = 4)
    {
        if (is_array($dates) && (1 === count($dates))) {
            $fromLabel = reset($dates);
            $dates = key($dates);
        } else {
            $fromLabel = $this->_('From');
        }
        if (is_string($dates)) {
            $element = new \Zend_Form_Element_Hidden(self::PERIOD_DATE_USED);
            $element->setValue($dates);
        } else {
            if (count($dates) >= $switchToSelect) {
                $element = $this->_createSelectElement(self::PERIOD_DATE_USED, $dates);
                $element->setLabel($this->_('For date'));

                $fromLabel = '';
            } else {
                $element = $this->_createRadioElement(self::PERIOD_DATE_USED, $dates);
                $element->setSeparator(' ');

                $fromLabel = html_entity_decode(' &raquo; ',  ENT_QUOTES, 'UTF-8');
            }
            $fromLabel .= $this->_('from');

            if ((null === $defaultDate) || (! isset($dates[$defaultDate]))) {
                // Set value to first key
                reset($dates);
                $defaultDate = key($dates);
            }
            $element->setValue($defaultDate);
        }
        $elements[self::PERIOD_DATE_USED] = $element;

        $this->periodSelectRepository->addZendPeriodSelectors($elements, $fromLabel);
    }
}