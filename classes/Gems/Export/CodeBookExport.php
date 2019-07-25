<?php

/**
 *
 * @package    Pulse
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Export;


use Gems\Export\ExportAbstract;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Common\Type;

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class CodeBookExport extends StreamingExcelExport
{
    /**
     * @return string name of the specific export
     */
    public function getName() {
        return 'CodeBookExport';
    }

}