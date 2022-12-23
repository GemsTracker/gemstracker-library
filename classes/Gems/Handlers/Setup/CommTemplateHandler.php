<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Model\CommTemplateModel;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\DependencyResolver\ConstructorDependencyResolver;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class CommTemplateHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = ['commTemplates'];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    //protected array $createEditSnippets = ['Mail\\MailModelFormSnippet'];

    protected ProjectOverloader $overloader;

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    //protected array $showSnippets = ['Mail\\CommTemplateShowSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        ProjectOverloader $projectOverloader,
    )
    {
        parent::__construct($responder, $translate);
        $this->overloader = clone $projectOverloader;
        $this->overloader->setDependencyResolver(new ConstructorDependencyResolver());
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel(bool $detailed, string $action): ModelAbstract
    {
        /**
         * @var ModelAbstract
         */
        return $this->overloader->create(CommTemplateModel::class);
        /*$allLanguages    = $this->util->getLocalized()->getLanguages();
        $currentLanguage = $this->locale->getLanguage();
        $markEmptyCall   = array($this->translatedUtil, 'markEmpty');

        ksort($allLanguages);

        $model = $this->loader->getModels()->getCommtemplateModel($currentLanguage);

        $commTargets = $this->loader->getMailTargets();

        $model->set('gct_name', 'label', $this->_('Name'), 'size', 50);
        $model->set('gct_target', 'label', $this->_('Mail Target'),
                'multiOptions', $commTargets,
                'formatFunction', array($this, '_')
                );
        
        // If the token target is available, use it as a default
        if (array_key_exists('token', $commTargets)) {
            $model->set('gct_target', 'default', 'token');
        }

        $model->set('gct_code', 'label', $this->_('Template code'),
                'description', $this->_('Optional code name to link the template to program code.'),
                'formatFunction', $markEmptyCall,
                'size', 50
                );

        // SUB TRANSLATION MODEL
        $translationModel = new \MUtil\Model\TableModel('gems__comm_template_translations', 'gctt');

        $translationModel->set('gctt_lang', 'multiOptions', $allLanguages);

        if ($action === 'index') {
            $translationModel->set('gctt', 'label', $this->_('Subject'),
                    'size', 50,
                    'formatFunction', array($this, 'displayMultipleSubjects')
                    );
        } else {
            $translationModel->set('gctt_subject', 'label', $this->_('Subject'),
                    'size', 100,
                    'formatFunction', $markEmptyCall
                    );
        }

        if ($detailed) {
            $translationModel->set('gctt_body', 'label', $this->_('Message'),
                    'cols', 60,
                    'decorators', array('CKEditor'),
                    'elementClass', 'textarea',
                    'formatFunction', array($this, 'bbToHtml'),
                    'rows', 8
                    );
        }

        if ($this->project->getEmailMultiLanguage()) {
            $requiredRows = array();
            foreach($allLanguages as $code=>$language) {
                $requiredRows[]['gctt_lang'] = $code;
            }
        } else {
            $defaultLanguage = 'en';
            if (isset($this->config['locale'], $this->config['locale']['default'])) {
                $defaultLanguage = $this->config['locale']['default'];
            }
            $requiredRows[]['gctt_lang'] = $defaultLanguage;

            $translationModel->setFilter(array('gctt_lang' => $defaultLanguage));
        }

        $transformer = new \MUtil\Model\Transform\RequiredRowsTransformer();
        $transformer->setRequiredRows($requiredRows);
        $translationModel->addTransformer($transformer);


        $model->addModel($translationModel, array('gct_id_template' => 'gctt_id_template'), 'gctt');

        return $model;*/
    }

    /**
     *
     * @param array $subValuesArray
     * @return string
     */
    public function displayMultipleSubjects($subValuesArray)
    {
        $html = \MUtil\Html::create()->div();
        $output = '';

        $multi = false;
        if (count($subValuesArray) > 1) {
            $multi = true;
        }
        foreach($subValuesArray as $subitem) {
            if (!empty($subitem['gctt_subject'])) {
                $paragraph = $html->p();
                if ($multi) {
                    $paragraph->strong()->append($subitem['gctt_lang'].':');
                    $paragraph->br();
                }
                $paragraph[] = $subitem['gctt_subject'];
            }
        }
        return $html;
    }

    public function getCreateTitle(): string
    {
        return $this->_('New template');
    }

    public function getEditTitle(): string
    {
        $data = $this->getModel()->loadFirst();

        return sprintf($this->_('Edit template: %s'), $data['gct_name']);
    }

    public function getIndexTitle(): string
    {
        return $this->_('Email templates');
    }
}
