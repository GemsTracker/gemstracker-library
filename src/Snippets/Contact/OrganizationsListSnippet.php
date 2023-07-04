<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Contact
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Contact;

use Gems\Project\ProjectSettings;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;
use Zalt\Snippets\TranslatableSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Contact
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 2.0
 */
class OrganizationsListSnippet extends TranslatableSnippetAbstract
{
    /**
     *
     * @var array org-id => key => value
     */
    protected array $organizations;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo, 
        TranslatorInterface $translate,
        protected ProjectSettings $project
    ) {
        $this->project = $project;

        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $orgCount = count($this->organizations);

        switch ($orgCount) {
            case 0:
                $html->pInfo(sprintf($this->_('%s is still under development.'), $this->project->getName()));
                break;

            case 1:
                $organization = reset($this->organizations);

                $p = $html->pInfo(sprintf($this->_('%s is run by: '), $this->project->getName()));
                $p->a($organization['gor_url'], $organization['gor_name']);
                $p->append('.');

                $html->pInfo()->sprintf(
                    $this->_('Please contact the %s if you have any questions regarding %s.'),
                    $organization['gor_name'],
                    $this->project->getName()
                );
                break;

            default:
                $p = $html->pInfo(sprintf(
                    $this->_('%s is a collaboration of these organizations:'),
                    $this->project->getName()
                ));

                $data = \MUtil\Lazy::repeat($this->organizations);
                $ul = $p->ul($data, ['class' => 'indent']);
                $li = $ul->li();
                $li->a($data->gor_url->call($this, '_'), $data->gor_name, ['rel' => 'external']);
                $li->append(' (');
                $li->append($data->gor_task->call([$this, '_']));
                $li->append(')');

                $html->pInfo()->sprintf(
                    $this->_('You can contact any of these organizations if you have questions regarding %s.'),
                    $this->project->getName()
                );
                break;
        }

        return $html;
    }
}
