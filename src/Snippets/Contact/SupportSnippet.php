<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Contact;

use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;
use Zalt\Snippets\TranslatableSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 14:02:33
 */
class SupportSnippet extends TranslatableSnippetAbstract
{
    /**
     *
     * @var array org-id => name
     */
    protected array $organizations;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo, 
        TranslatorInterface $translate,
        protected array $config
    ) {
        $this->config = $config;

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

        $html->h3($this->_('Support'));
        $html->pInfo()->sprintf(
            $this->_('There is more than one way to get support for %s.'),
            $this->config['app']['name'] ?? '',
        );

        if (isset($this->config['contact'], $this->config['contact']['docsUrl'])) {
            $url = $this->config['contact']['docsUrl'];
            $html->h4($this->_('Documentation'));

            $html->pInfo()->sprintf($this->_('All available documentation is gathered at: %s'))
                ->a($url, ['rel' => 'external', 'target' => 'documentation']);
        }

        if (isset($this->config['contact'], $this->config['contact']['manualUrl'])) {
            $url = $this->config['contact']['manualUrl'];
            $html->h4($this->_('Manual'));

            $html->pInfo()->sprintf($this->_('The manual is available here: %s'))
                ->a($url, ['rel' => 'external', 'target' => 'manual']);
        }

        if (isset($this->config['contact'], $this->config['contact']['forumUrl'])) {
            $url = $this->config['contact']['forumUrl'];
            $html->h4($this->_('The forum'));

            $html->pInfo()->sprintf($this->_(
                'You will find questions asked by other users and ask new questions at our forum site: %s'
            ))->a($url, ['rel' => 'external', 'target' => 'forum']);
        }

        if (isset($this->config['contact'], $this->config['contact']['supportUrl'])) {
            $url = $this->config['contact']['supportUrl'];
            $html->h4($this->_('Support site'));

            $html->pInfo()->sprintf($this->_('Check our support site at %s.'))
                ->a($url, ['rel' => 'external', 'target' => 'support']);
        }

        if (count($this->organizations) == 1) {
            $html->h4($this->_('Or contact this organization'));
            $organization = reset($this->organizations);
            $p = $html->pInfo();
            $p->a($organization['gor_url'], $organization['gor_name']);
        } elseif (count($this->organizations) > 1) {
            $html->h4($this->_('Or contact any of these organizations'));
            $data = \MUtil\Lazy::repeat($this->organizations);
            $p = $html->pInfo();
            $ul = $p->ul($data, array('class' => 'indent'));
            $li = $ul->li();
            $li->a($data->gor_url->call($this, '_'), $data->gor_name, array('rel' => 'external'));
        }

        return $html;
    }
}
