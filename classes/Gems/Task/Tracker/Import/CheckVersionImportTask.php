<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 22, 2016 2:16:21 PM
 */
class CheckVersionImportTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param array $versionData Nested array of trackdata
     */
    public function execute($versionData = null)
    {
        $batch = $this->getBatch();

        switch (count((array) $versionData)) {
            case 0:
                // Can be enabled in 1.7.3, for now leave it for testing
                // $batch->addToCounter('import_errors');
                // $batch->addMessage($this->_('No "version" data found in import file.'));
                // break;

            case 1;
                break;

            default:
                $batch->addToCounter('import_errors');
                $batch->addMessage(sprintf(
                        $this->_('%d sets of "version" data found in import file.'),
                        count($versionData)
                        ));
                foreach ($tracksData as $lineNr => $versionData) {
                    $batch->addMessage(sprintf(
                            $this->_('"version" data found on line %d.'),
                            $lineNr
                            ));
                }
        }
    }
}
