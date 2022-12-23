<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail;

use Gems\Model\CommTemplateModel;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class MailModelFormSnippet extends \Gems\Snippets\ModelFormSnippetAbstract
{
    /**
     * @var string The current selected language tab
     */
    protected $_currentLanguage;
    
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var \Zend_Locale
     */
    protected $locale;
    
    /**
     *
     * @var \Gems\Mail\MailElements
     */
    protected $mailElements;

    /**
     *
     * @var \Gems\Mail\MailerAbstract
     */
    protected $mailer;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    
    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addBridgeElements(FormBridgeInterface $bridge, FullDataInterface $model)
    {
        $this->mailElements->setForm($bridge->getForm());
        $this->initItems();
        $this->addItems($bridge, 'gct_name');
        $this->addItems($bridge, 'gct_id_template', 'gct_target');
        $this->addItems($bridge, 'gct_code');

        $bridge->getForm()->getElement('gct_target')->setAttrib('onchange', 'this.form.submit()');

        $this->mailElements->addFormTabs($bridge, 'gctt', 
                                         'active', $this->_currentLanguage, 
                                         'tabcolumn', 'gctt_lang', 
                                         'selectedTabElement', 'send_language');

        $config = array(
        'extraPlugins' => 'bbcode,availablefields',
        'toolbar' => array(
            array('Source','-','Undo','Redo'),
            array('Find','Replace','-','SelectAll','RemoveFormat'),
            array('Link', 'Unlink', 'Image', 'SpecialChar'),
            '/',
            array('Bold', 'Italic','Underline'),
            array('NumberedList','BulletedList','-','Blockquote'),
            array('Maximize'),
            array('availablefields')
            )
        );

        $mailfields = array_map('utf8_encode', $this->mailer->getMailFields());
        ksort($mailfields);
        $config['availablefields'] = $mailfields;

        $config['availablefieldsLabel'] = $this->_('Fields');
        $this->view->inlineScript()->prependScript("
            CKEditorConfig = ".\Zend_Json::encode($config).";
            ");

        $bridge->addFakeSubmit('preview', array('label' => $this->_('Preview')));

        $bridge->addElement($this->mailElements->createEmailElement('to', $this->_('To (test)'), false));
        $bridge->addElement($this->mailElements->createEmailElement('from', $this->_('From'), false));

        //$bridge->addRadio('send_language', array('label' => $this->_('Test language'), 'multiOptions' => ))
        $bridge->addHidden('send_language');
        $bridge->addFakeSubmit('sendtest', array('label' => $this->_('Send (test)')));

        $bridge->addElement($this->getSaveButton($bridge->getForm()));

        // $bridge->addElement($this->mailElements->createPreviewHtmlElement($this->_('Preview HTML')));
        // $bridge->addElement($this->mailElements->createPreviewTextElement($this->_('Preview Text')));
        
        $bridge->addHtml('available_fields', array('label' => $this->_('Available fields')));
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->mailElements = $this->loader->getMailLoader()->getMailElements();
        $this->mailTargets  = $this->loader->getMailLoader()->getMailTargets();

        parent::afterRegistry();
    }

    /**
     * @param string $language 
     * @return mixed|null The language if it exists in the template, otherwise null 
     */
    protected function checkLanguage($language)
    {
        foreach($this->formData['gctt'] as $languageId => $templateLanguage) {
            // Find the current template (for multi language projects)
            if ($templateLanguage['gctt_lang'] == $language) {
                return $language;
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        if ($this->model instanceof CommTemplateModel)
        {
            $sub = $this->model->get('gctt', 'model');
            
            if ($sub instanceof \MUtil\Model\ModelAbstract && (! $sub->has('preview_html'))) {
                $sub->set('preview_html', 'label', $this->_('Preview HTML'),
                    'elementClass', 'Html',
                    'noHidden', true
                    );
//                $sub->set('preview_text', 'label', $this->_('Preview Text'),
//                          'elementClass', 'Html',
//                          'noHidden', true
//                );
            }
        }
        
        return $this->model;
    }

    /**
     * Style the template previews
     * @param  array $templateArray template data
     * @return array html and text views
     */
    protected function getPreview(&$templateArray)
    {
        $multi = false;
        if (count($templateArray) > 1) {
            $multi = true;
            $allLanguages = $this->util->getLocalized()->getLanguages();
        }

        $htmlView = \MUtil\Html::create()->div();
        $textView = \MUtil\Html::create()->div();

        foreach($templateArray as &$template) {
            $content = '';
            if ($template['gctt_subject'] || $template['gctt_body']) {
                if ($multi) {
                    $htmlView->h3()->append($allLanguages[$template['gctt_lang']]);
                    $textView->h3()->append($allLanguages[$template['gctt_lang']]);
                }
                
                // Set the translate adapter to the local language
                $locale = new \Zend_Locale($template['gctt_lang']);
                $this->translateAdapter->setLocale($locale);
                $this->mailer->setLanguage($template['gctt_lang'], true);
                
                $content .= '[b]';
                $content .= $this->_('Subject:');
                $content .= '[/b] [i]';
                $content .= $this->mailer->applyFields($template['gctt_subject']);
                $content .= "[/i]\n\n";
                $content .= $this->mailer->applyFields($template['gctt_body']);

                $template['preview_html'] = \MUtil\Html::create()->div(['class' => 'mailpreview']);
                $template['preview_html']->raw(\MUtil\Markup::render($content, 'Bbcode', 'Html'));
                $template['preview_text'] = \MUtil\Html::create()->pre(['class' => 'mailpreview']);
                $template['preview_text']->raw(wordwrap(\MUtil\Markup::render($content, 'Bbcode', 'Text'), 80));

                $htmlView->append($template['preview_html']);
                $textView->append($template['preview_text']);
                
            }
        }

        // Reset fields to the current language 
        $this->translateAdapter->setLocale($this->locale);
        $this->mailer->setLanguage($this->locale->getLanguage(), true);
        
        return array('html' => $htmlView, 'text' => $textView);
    }

    protected function getSaveButton($form)
    {
        $options = array('label' => $this->_('Save'),
                  'attribs' => array('class' => $this->buttonClass)
                );
        return $form->createElement('submit', $this->saveButtonId , $options);
    }

    /**
     * Load extra data not from the model into the form
     */
    protected function loadFormData(): array
    {
        parent::loadFormData();
        $this->loadMailer();

        $this->formData['available_fields'] = $this->mailElements->displayMailFields($this->mailer->getMailFields());

        if (isset($this->formData['gctt'])) {
            $multi = false;
            if (count($this->formData['gctt']) > 1) {
                $multi = true;
                $allLanguages = $this->util->getLocalized()->getLanguages();
            }

            $preview = $this->getPreview($this->formData['gctt']);

            $this->formData['preview_html'] = $preview['html'];
            $this->formData['preview_text'] = $preview['text'];
        }
        if (!isset($this->formData['to'])) {
            $organization = $this->mailer->getOrganization();
            $this->formData['to'] = $this->formData['from'] = $this->currentUser->getEmailAddress();
            if ($organization->getEmail()) {
                $this->formData['from'] = $organization->getEmail();
            } elseif ($this->project->getSiteEmail ()) {
                $this->formData['from'] = $this->project->getSiteEmail();
            }
        }

        if (! $this->_currentLanguage && isset($this->formData['send_language'])) {
            $this->_currentLanguage = $this->checkLanguage($this->formData['send_language']);
        }
        if (! $this->_currentLanguage) {
            $this->_currentLanguage = $this->checkLanguage($this->locale->getLanguage());
            if (! $this->_currentLanguage) {
                $this->_currentLanguage = $this->project->getLocaleDefault();
            }
        }
        return $this->formData;
    }

    /**
     * Loads the correct mailer
     */
    protected function loadMailer()
    {
        $this->mailTarget = false;

        if (isset($this->formData['gct_target']) && isset($this->mailTargets[$this->formData['gct_target']])) {
            $this->mailTarget = $this->formData['gct_target'];
        } else {
            reset($this->mailTargets);
            $this->mailTarget = key($this->mailTargets);
        }
        $this->mailer = $this->loader->getMailLoader()->getMailer($this->mailTarget);
    }

    /**
     * When the form is submitted with a non 'save' button
     */
    protected function onFakeSubmit()
    {
        if ($this->request->isPost()) {

            if (! empty($this->formData['preview'])) {
                $this->addMessage($this->_('Preview updated'));
                \MUtil\EchoOut\EchoOut::track(array_keys($this->formData), $this->formData['select_template']);
                return;
            }

            if (! empty($this->formData['sendtest'])) {
                // \MUtil\EchoOut\EchoOut::track(array_keys($this->formData['gctt'][1]), $this->formData['send_language']);
                $this->mailer->setTo($this->formData['to']);

                // Make sure at least one template is set (for single language projects)
                $template   = reset($this->formData['gctt']);
                $languageId = key($this->formData['gctt']);
                foreach($this->formData['gctt'] as $languageId => $templateLanguage) {
                    // Find the current template (for multi language projects)
                    if ($templateLanguage['gctt_lang'] == $this->_currentLanguage) {
                        $template = $templateLanguage;
                    }
                }

                // \MUtil\EchoOut\EchoOut::track($this->formData);
                $errors = false;
                if (! $template['gctt_subject']) {
                    $this->addMessage(sprintf(
                            $this->_('Subject required for %s part.'),
                            strtoupper($template['gctt_lang'])
                            ));
                    $errors = true;
                }
                if (! $template['gctt_body']) {
                    $this->addMessage(sprintf(
                            $this->_('Body required for %s part.'),
                            strtoupper($template['gctt_lang']))
                            );
                    $errors = true;
                }

                if ($errors) {
                   return;
                }

                $this->mailer->setFrom($this->formData['from']);
                $this->mailer->setSubject($template['gctt_subject']);
                $this->mailer->setBody($template['gctt_body'], 'Bbcode');
                $this->mailer->setTemplateId($this->formData['gct_id_template']);
                $this->mailer->send();

                $this->addMessage(sprintf($this->_('Test mail sent to %s'), $this->formData['to']));
            }
        }
    }
}