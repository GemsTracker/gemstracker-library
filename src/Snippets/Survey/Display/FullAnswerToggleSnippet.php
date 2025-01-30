<?php
/**
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Survey\Display;

use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\Generic\ButtonRowTrait;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Raw;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Display survey answers with a toggle for full or compact view
 *
 * @package    Gems
 * @subpackage Snippets\Survey\Display
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class FullAnswerToggleSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    use ButtonRowTrait;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        $this->addCurrentParent   = true;
    }

    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        // @phpstan-ignore method.notFound
        $html->hr(['class'=>'noprint']);

        $fullAnswers = $this->requestInfo->getParam('fullanswers', 0);

        if ($fullAnswers) {
            $link = $this->menuHelper->getRouteUrl($this->menuHelper->getCurrentRoute(), $this->requestInfo->getParams());
        } else {
            $link = $this->menuHelper->getRouteUrl($this->menuHelper->getCurrentRoute(), $this->requestInfo->getParams(), ['fullanswers' => 1]);
        }

        $container = $html->div(['class' => 'buttons', 'renderClosingTag' => true]);
        $container->append(Html::actionLink($link, $fullAnswers ? $this->_('Show only scores') : $this->_('Show all answers')));

        $menuList = $this->getButtons();

        if (count($menuList)) {
            foreach($menuList as $buttonInfo) {
                if (isset($buttonInfo['label'])) {
                    if (isset($buttonInfo['disabled']) && $buttonInfo['disabled'] === true) {
                        $container->append(Html::actionDisabled(Raw::raw($buttonInfo['label'])));
                    } elseif (isset($buttonInfo['url'])) {
                        $container->append(Html::actionLink($buttonInfo['url'], Raw::raw($buttonInfo['label'])));
                    }
                }
            }
        }

        // @phpstan-ignore method.notFound
        $html->hr(['class'=>'noprint']);

        return $html;
    }

    public function hasHtmlOutput(): bool
    {
        // Only show toggle for individual answer display
        if ($this->requestInfo->getCurrentAction() !== 'answer') {
            return false;
        }

        return true;
    }
}