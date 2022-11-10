<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class FileImportSnippet extends \MUtil\Snippets\ModelSnippetAbstract
{
    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $data = $this->getModel()->loadFirst();

        if ($data && isset($data['relpath'])) {
            $nameForUser = $data['relpath'];
        } else {
            $nameForUser = $this->request->getParam(\MUtil\Model::REQUEST_ID, $this->_('unknown'));
        }

        if (! ($data && file_exists($data['fullpath']))) {
            $this->addMessage(sprintf($this->_('The file "%s" does not exist on the server.'), $nameForUser));
            return \MUtil\Html::create('pInfo', sprintf($this->_('The file "%s" could not be imported.'), $nameForUser));
        }

        // \MUtil\EchoOut\EchoOut::track($data);
        return $this->_('Token');
    }
}
