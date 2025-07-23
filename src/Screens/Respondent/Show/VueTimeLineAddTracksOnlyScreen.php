<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Screens\Respondent\Show;

use Gems\Screens\ShowScreenAbstract;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Vue\PatientVueSnippet;
use Zalt\Html\HtmlInterface;

/**
 * @package    Gems
 * @subpackage Screens\Respondent\Show
 * @since      Class available since version 1.0
 */
class VueTimeLineAddTracksOnlyScreen extends ShowScreenAbstract
{
    public function getParameters(): array
    {
        return  [
            'addCurrentParent' => true,
            'respondent'   => 'getRespondent',  // Sets menu
            '-run-once' => 'openedRespondent',
            'tag' => 'show-respondent',
            'vueOptions' => [
                ':show-add-dropdown' => 1,
//            ':show-button-rows' => 0,
                ':show-buttons' => 0,
                ':show-organization-tabs' => 0,
                ':show-display-picker' => 0,
                ':show-respondent-info' => 0,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getScreenLabel(): HtmlInterface|string
    {
        return $this->translator->_('Vue Timeline and Add Tracks only view');
    }

    public function getSnippets(): array|bool
    {
        return [
            ContentTitleSnippet::class,
            'Respondent\\MultiOrganizationTab',
            'Respondent\\RespondentDetailsSnippet',
            CurrentButtonRowSnippet::class,
            PatientVueSnippet::class,
        ];
    }
}