<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Token;

use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\TabSnippetAbstract;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Respondent token filter tabs
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.1
 */
class TokenTabsSnippet extends TabSnippetAbstract
{
    /**
     * The RESPONDENT model, not the token model
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuSnippetHelper,
        protected Translator $translator,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuSnippetHelper);
    }

    /**
     * Return optionally the single parameter key which should left out for the default value,
     * but is added for all other tabs.
     *
     * @return mixed
     */
    protected function getParameterKey()
    {
        return 'filter';
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs(): array
    {
        $tabs['default'] = [$this->translator->_('Default'), 'title' => $this->translator->_('To do 2 weeks ahead and done')];
        $tabs['todo']    = $this->translator->_('To do');
        $tabs['done']    = $this->translator->_('Done');
        $tabs['missed']  = $this->translator->_('Missed');
        $tabs['all']     = $this->translator->_('All');

        return $tabs;
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
        $reqFilter = null;
        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams['filter'])) {
            $reqFilter = $queryParams['filter'];
        }
        switch ($reqFilter) {
            case 'todo':
                //Only actions valid now that are not already done
                $filter[] = 'gto_completion_time IS NULL';
                $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                break;
            case 'done':
                //Only completed actions
                $filter[] = 'gto_completion_time IS NOT NULL';
                break;
            case 'missed':
                //Only missed actions (not filled in, valid until < today)
                $filter[] = 'gto_completion_time IS NULL';
                $filter[] = 'gto_valid_until < CURRENT_TIMESTAMP';
                break;
            case 'all':
                $filter[] = 'gto_valid_from IS NOT NULL';
                break;
            default:
                //2 weeks look ahead, valid from date is set
                $filter[] = 'gto_valid_from IS NOT NULL';
                $filter[] = 'DATEDIFF(gto_valid_from, CURRENT_TIMESTAMP) < 15';
                $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
        }
        $this->model->getMetaModel()->setMeta('tab_filter', $filter);

        return parent::hasHtmlOutput();
    }
}
