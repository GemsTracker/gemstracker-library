<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Task\Tracker;

use Gems\Db\ResultFetcher;
use Laminas\Db\Sql\Select;
use MUtil\Task\TaskAbstract;

/**
 * @package    Gems
 * @subpackage Task\Tracker
 * @since      Class available since version 1.0
 */
class LoadTokensFor extends TaskAbstract
{
    const LIMIT = 1000;

    /**
     * @inheritDoc
     */
    public function execute($rowTask = '', $offset = 0, $userId = null)
    {
        $batch = $this->getBatch();

        if (! $rowTask) {
            $batch->setMessage('tokensLoadMsg', $this->_('Incorrect parameters for loading token task. ') . $rowTask);
            return;
        }

        /**
         * @var ResultFetcher $resultFetcher
         */
        $resultFetcher = $batch->getVariable(ResultFetcher::class);
        /**
         * @var Select $select
         */
        $select = $batch->getVariable(Select::class);;
        if (! $select instanceof Select) {
            $batch->setMessage('tokensLoadMsg', $this->_('No select set for token task. ') . $rowTask);
            return;
        }
        $select->offset($offset)
            ->limit(self::LIMIT);

        $result = $resultFetcher->fetchAll($select);

        if (! $result) {
            if (! $offset) {
                $batch->setMessage('tokensFinishedMsg', $this->_('No tokens to load.'));
            } else {
                $batch->setMessage('tokensFinishedMsg', sprintf($this->_('Finished after laoding %d tokens.'), $batch->getCounter('tokensLoaded')));
            }
            return;
        }
        $batch->addToCounter('tokensLoaded', count($result));
        $batch->setMessage('tokensLoadedMsg', sprintf($this->_('%d tokens loaded.'), $batch->getCounter('tokensLoaded')));

        foreach ($result as $tokenData) {
            $tokenId = $tokenData['gto_id_token'];
            $batch->setTask($rowTask, 'tokchk-' . $tokenId, $tokenId, $userId);
        }

        $batch->addTask('Tracker\\LoadTokensFor', $rowTask, $offset + self::LIMIT, $userId);
    }
}