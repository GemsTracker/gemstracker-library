<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Generic;

use Gems\Html;
use Zalt\Html\Raw;

/**
 * @package    Gems
 * @subpackage Snippets\Generic
 * @since      Class available since version 1.0
 */
class CurrentButtonColumnSnippet extends CurrentButtonRowSnippet
{
    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     */
    public function getHtmlOutput()
    {
        $menuList = $this->getButtons();

        if (count($menuList)) {
            $container = Html::create('div', array('class' => 'buttons', 'renderClosingTag' => true));
            foreach($menuList as $buttonInfo) {
                if (isset($buttonInfo['label'])) {
                    if (isset($buttonInfo['disabled']) && $buttonInfo['disabled'] === true) {
                        $container->append(Html::actionDisabled(Raw::raw($buttonInfo['label'])));
                    } elseif (isset($buttonInfo['url'])) {
                        $container->append(Html::actionLink($buttonInfo['url'], Raw::raw($buttonInfo['label'])));
                    }
                    $container->br();
                }
            }

            return $container;
        }
        return null;
    }
}