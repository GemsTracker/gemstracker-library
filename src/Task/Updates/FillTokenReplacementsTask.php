<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Updates
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Updates;

/**
 *
 * @package    Gems
 * @subpackage Task\Updates
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Mar 2, 2017 1:38:59 PM
 */
class FillTokenReplacementsTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var string
     */
    protected $_lastTokenId;

    /**
     *
     * @var \Zend_Db_Statement_Interface
     */
    protected $_stmt;

    /**
     *
     * @var boolean
     */
    protected $_stopped = false;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if (! $this->db instanceof \Zend_Db_Adapter_Abstract) {
            return false;
        }

        $select = $this->db->select();
        $select->from(['nw'=> 'gems__tokens'], [
            'gtrp_id_token_new' => 'gto_id_token',
            'gtrp_created' => 'gto_created',
            'gtrp_created_by' => 'gto_created_by',
            ])->joinInner(
                    ['pr' => 'gems__tokens'],
                    "nw.gto_id_respondent_track = pr.gto_id_respondent_track AND
                     nw.gto_id_round            = pr.gto_id_round AND
                     nw.gto_round_order         = pr.gto_round_order AND
                     nw.gto_created             > pr.gto_created
                     ",
                    ['gtrp_id_token_old' => 'gto_id_token',]
                    )
                ->where('nw.gto_id_token NOT IN (SELECT gtrp_id_token_new FROM gems__token_replacements)')
                ->order('nw.gto_id_token')
                ->order('pr.gto_created');

        $this->_stmt = $this->db->query($select);

        return parent::checkRegistryRequestsAnswers();
    }

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute()
    {
        $row = $this->_stmt->fetch();

        $batch = $this->getBatch();
        $count = $batch->getCounter('inserted_token_replacements');

        if ($row['gtrp_id_token_new'] == $this->_lastTokenId) {
            if (! $count) {
                $batch->addMessage($this->_('No token replacements to create'));
            }
            $this->_stopped = true;
            return;
        }

        $this->db->insert('gems__token_replacements', $row);

        $count = $batch->addToCounter('inserted_token_replacements');
        $batch->setMessage('inserted_replacements', sprintf(
                $this->plural('Created %d token replacement', 'Created %d token replacements', $count),
                $count
                ));

        $this->_lastTokenId = $row['gtrp_id_token_new'];
    }

    /**
     * Return true when the task has finished.
     *
     * @return boolean
     */
    public function isFinished()
    {
        return $this->_stopped;
    }
}
