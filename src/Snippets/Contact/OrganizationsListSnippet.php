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
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\Late;
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

                $data = Late::repeat($this->organizations);
                $ul = $p->ul($data, ['class' => 'indent']);
                $li = $ul->li();
                $li->a($data->__get('gor_url'), $data->__get('gor_name'), ['rel' => 'external']);
                $li->append(' (');
                $li->append($data->__get('gor_task'));
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
