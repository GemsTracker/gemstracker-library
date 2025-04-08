<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Screens\Respondent\Show;

use Gems\Screens\ShowScreenAbstract;
use Gems\Snippets\Vue\PatientVueSnippet;
use Zalt\Html\HtmlInterface;

/**
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @since      Class available since version 1.0
 */
class VueTimelineScreen extends ShowScreenAbstract
{
    /**
     * @inheritDoc
     */
    public function getScreenLabel(): HtmlInterface|string
    {
        return $this->translator->_('Vue Timeline view');
    }

    public function getSnippets(): array|bool
    {
        return [
            PatientVueSnippet::class,
        ];
    }
}