<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Task\Tracker;

use Gems\Db\ResultFetcher;
use Laminas\Db\Sql\Expression;

/**
 * @package    Gems
 * @subpackage Task\Tracker
 * @since      Class available since version 1.0
 */
class LoadTracksFor extends \MUtil\Task\TaskAbstract
{
    protected $db;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($maxTrackId = null, array $where =[], $rowTask = null, $userId = null)
    {
        $batch         = $this->getBatch();
        /**
         * @var ResultFetcher $resultFetcher
         */
        $resultFetcher = $batch->getVariable(ResultFetcher::class);

        $respTrackSelect = $resultFetcher->getSelect('gems__respondent2track');
        $respTrackSelect->columns(['gr2t_id_respondent_track'])
            ->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code', [])
            ->join('gems__tracks', 'gr2t_id_track = gtr_id_track', [])
            ->where($where)
            ->where(['gr2t_id_respondent_track <= ' . intval($maxTrackId)])
            ->limit(1000);

        $respTrackId = null;
        foreach ($resultFetcher->fetchCol($respTrackSelect) as $respTrackId) {
            $batch->setTask($rowTask, 'trkldd-' . $respTrackId, $respTrackId, $userId);
            $batch->addToCounter('resptracks');
        }
        $batch->setMessage('respLoaded', sprintf($this->_('%d tracks loaded.'), $batch->getCounter('resptracks')));

        if ($respTrackId !== null) {
            $batch->addTask('Tracker\\LoadTracksFor', $respTrackId + 1, $where, $rowTask, $userId);
        }
    }
}