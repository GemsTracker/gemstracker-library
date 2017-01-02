<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_RespondentSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);

        $user = $this->loader->getCurrentUser();
        if ($user->hasPrivilege('pr.respondent.select-on-track')) {
            $tracks = $this->searchData['__active_tracks'];

            $masks['show_all']           = $this->_('(all)');
            $masks['show_without_track'] = $this->_('(no track)');
            if (count($tracks) > 1) {
                $masks['show_with_track']    = $this->_('(with track)');
            }

            if (count($tracks) > 1) {
                $elements['gr2t_id_track'] = $this->_createSelectElement('gr2t_id_track', $masks + $tracks);
            } else {
                $element = $this->_createRadioElement('gr2t_id_track', $masks + $tracks);
                $element->setSeparator(' ');
                $elements['gr2t_id_track'] = $element;
            }
            $lineBreak = true;
        } else {
            $lineBreak = false;
        }

        if ($user->hasPrivilege('pr.respondent.show-deleted')) {
            $elements['grc_success'] = $this->_createCheckboxElement('grc_success', $this->_('Show active'));
        }

        if ($this->model->isMultiOrganization()) {
            $element = $this->_createSelectElement(
                    \MUtil_Model::REQUEST_ID2,
                    $user->getRespondentOrganizations(),
                    $this->_('(all organizations)')
                    );

            if ($lineBreak) {
                $element->setLabel($this->_('Organization'))
                        ->setAttrib('onchange', 'this.form.submit();');
                $elements[] = \MUtil_Html::create('br');
            }
            $elements[\MUtil_Model::REQUEST_ID2] = $element;
        }

        return $elements;
    }
}
