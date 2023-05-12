<?php


namespace Gems\Snippets\Database;


use Gems\Snippets\FormSnippetAbstractMUtil;

/**
 * Class RunSqlFormSnippet
 * A snippet to show the run sql form
 *
 * @package Gems\Snippets\Database
 */
class RunSqlFormSnippet extends FormSnippetAbstractMUtil
{
    /**
     * @var array of menu links to show under the form
     */
    public $menuLinks;

    /**
     * @var \MUtil\Model\ModelAbstract
     */
    public $model;

    /**
     * @var array The query result
     */
    protected $result;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addFormElements(\Zend_Form $form)
    {
        $element = $form->createElement('textarea', 'script');
        $element->setDescription($this->_('Separate multiple commands with semicolons (;).'));
        $element->setLabel('SQL:');
        $element->setRequired(true);
        $form->addElement($element);

        $this->saveLabel = $this->_('Run');

        $this->addCsrf();
        $this->addSaveButton();
    }

    /**
     * Makes sure there is a form.
     */
    protected function loadForm()
    {
        if (! $this->_form) {
            $options = array();

            //$options['class'] = 'form-horizontal';
            $options['role'] = 'form';

            $this->_form = $this->createForm($options);

            $this->addFormElements($this->_form);
        }
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
        $output = parent::getHtmlOutput($view);
        if ($this->result) {
            $output->h3($this->_('Result sets'));
            $output[] = $this->getResultTable($this->result);
        }
        return $output;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList()
    {
        if ($this->menuLinks) {
            return $this->menuLinks;
        }
        return parent::getMenuList();
    }

    /**
     * Get the current model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function getModel()
    {
        return $this->model;
    }

    /**
     * Return an array attribute with the current resultset
     *
     * @return \MUtil\Html\ArrayAttribute
     */
    protected function getResultTable($results)
    {
        $resultSet = 1;
        $resultTable     = \MUtil\Html::create()->array();
        foreach ($results as $result) {
            if (is_string($result)) {
                $this->addMessage($result);
            } else {
                $resultRow = $resultTable->echo($result, sprintf($this->_('Result set %s.'), $resultSet++));
                $resultRow->class = 'browser';
            }
        }
        return $resultTable;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->_('Execute raw SQL');
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $resultSet = 0;
        if($this->request->isPost()) {
            $this->_form->populate($this->request->getPost());
        }
        if ($this->_saveButton->isChecked() && $this->_form->isValid($this->request->getPost())) {
            $data = $this->_form->getValues();
            $data['name'] = '';
            $data['type'] = $this->_('raw');

            $model = $this->getModel();
            $this->result = $model->runScript($data, true);
        }
        
        return 0;
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * #param array $params Url items to set for this route
     * @return \MUtil\Snippets\ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute(array $params = array())
    {
        return $this;
    }
}