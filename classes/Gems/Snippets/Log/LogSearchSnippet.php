<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: LogSearchSnippet.php $
 */

namespace Gems\Snippets\Log;

use Gems\Snippets\AutosearchInRespondentSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-feb-2015 19:46:34
 */
class LogSearchSnippet extends AutosearchInRespondentSnippet
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

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

        $this->_addPeriodSelectors($elements, 'gla_created');

        $elements[] = null;

        $elements[] = $this->_('Specific action');

        $sql = "SELECT gls_id_action, gls_name
                    FROM gems__log_setup
                    WHERE gls_when_no_user = 1 OR gls_on_action = 1 OR gls_on_change = 1 OR gls_on_post = 1
                    ORDER BY gls_name";

        $elements[] = $this->_createSelectElement('gla_action', $sql, $this->_('(any action)'));

        $elements[] = $this->_createSelectElement(
                'gla_organization',
                $this->loader->getCurrentUser()->getAllowedOrganizations(),
                $this->_('(all organizations)')
                );

        return $elements;
    }
}
