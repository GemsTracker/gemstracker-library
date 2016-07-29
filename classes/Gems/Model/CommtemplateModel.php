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