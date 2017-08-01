<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Model_CommtemplateModel extends \Gems_Model_JoinModel
{

    protected $locale;

    /**
     * Create the mail template model
     */
    public function __construct()
    {
        parent::__construct('commtemplate', 'gems__comm_templates', 'gct');
    }

    /**
     * Delete items from the model
     *
     * The filter is propagated using over $this->_joinFields.
     *
     * Table rows are only deleted when there exists a value in the filter for
     * ALL KEY FIELDS of that table. In other words: a partial key is not enough
     * to actually delete an item.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true, $saveTables = null)
    {
        $deleted = 0;

        // If we update a field instead of really deleting we don't delete the related record
        if (!$this->_deleteValues) {
            $saveTables = $this->_checkSaveTables($saveTables);
            $filter     = $this->_checkFilterUsed($filter);

            if (array_key_exists('gct_id_template', $filter)) {
                $id = $filter['gct_id_template'];
            }
        }
        
        $deleted = parent::delete($filter, $saveTables);

        // If we had an ID and the result was that we deleted something, propagate to the other model
        if ($id > 0 && $deleted > 0) {
            $model = new \MUtil_Model_TableModel('gems__comm_template_translations');
            $model->delete(['gctt_id_template' => $id]);
        }

        return $deleted;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     * /
      public function afterRegistry()
      {
      parent::afterRegistry();
      $currentLanguage = $this->locale->getLanguage();

      $this->addLeftTable(
      'gems__comm_template_translations',
      array(
      'gct_id_template' => 'gctt_id_template',
      'gctt_lang' => new \Zend_Db_Expr("'".$currentLanguage."'")
      ),
      'gctt');

      $this->setOnSave('gctt_lang', $currentLanguage);
      } // */
}
