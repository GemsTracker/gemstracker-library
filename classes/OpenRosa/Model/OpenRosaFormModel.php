<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace OpenRosa\Model;

/**
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class OpenRosaFormModel extends \Gems\Model\JoinModel
{
    /**
     *
     * @var \Zend_Translate_Adapter
     */
    public $translate;

    public function __construct()
    {
        parent::__construct('orf', 'gems__openrosaforms', 'gof');
    }

    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->setIfExists('gof_form_id', 'label', $this->translate->_('FormID'));
        $this->setIfExists('gof_form_version', 'label', $this->translate->_('Version'));
        $this->setIfExists('gof_form_title', 'label', $this->translate->_('Name'));
        $this->setIfExists('gof_form_active', 'label', $this->translate->_('Active'), 'elementClass', 'checkbox');
    }

    /**
     * Get a select statement using a filter and sort
     *
     * Modified to add the information schema, only possible like this since
     * the table has no primary key and can not be added using normal joins
     *
     * @param array $filter
     * @param array $sort
     * @return \Zend_Db_Table_Select
     */
    public function _createSelect(array $filter, array $sort)
    {
        $select = parent::_createSelect($filter, $sort);

        $config = $select->getAdapter()->getConfig();
        if (isset($config['dbname'])) {
            $constraint = $select->getAdapter()->quoteInto(' AND TABLE_SCHEMA=?', $config['dbname']);
        } else {
            $constraint = '';
        }
        $select->joinLeft('INFORMATION_SCHEMA.TABLES', "table_name  = convert(concat_ws('_','gems__orf_', REPLACE(gof_form_id,'.','_'),gof_form_version) USING utf8)" . $constraint, array('TABLE_ROWS'));
        return $select;
    }
}