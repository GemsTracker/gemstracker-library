<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Generic;

use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Raw;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Displays the parent menu item (if existing) plus any current
 * level buttons that are visible
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2
 */
class ButtonRowSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    use ButtonRowTrait;

    public function __construct(
        SnippetOptions              $snippetOptions,
        protected RequestInfo       $requestInfo,
        TranslatorInterface         $translate,
        protected MenuSnippetHelper $menuHelper
    )
    {
        parent::__construct($snippetOptions, $this->requestInfo, $translate);
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     */
    public function getHtmlOutput()
    {
        $menuList = $this->getButtons();

        var_dump($menuList); exit;

        if (count($menuList)) {
            $container = Html::create('div', array('class' => 'buttons', 'renderClosingTag' => true));
            foreach($menuList as $buttonInfo) {
                if (isset($buttonInfo['label'])) {
                    if (isset($buttonInfo['disabled']) && $buttonInfo['disabled'] === true) {
                        $container->append(Html::actionDisabled(Raw::raw($buttonInfo['label'])));
                    } elseif (isset($buttonInfo['url'])) {
                        $container->append(Html::actionLink($buttonInfo['url'], Raw::raw($buttonInfo['label'])));
                    }
                }
            }

            return $container;
        }
        return null;
    }
}
