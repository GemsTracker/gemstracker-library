<?php

/**
 *
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model\Type;

use Gems\User\Mask\MaskRepository;
use MUtil\Model\Type\JsonData;
use Zalt\Html\ElementInterface;
use Zalt\Html\HtmlElement;

/**
 *
 * @package    Gems
 * @subpackage Model\Type
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 2, 2017 2:45:45 PM
 */
class MaskedJsonData extends JsonData
{
    /**
     *
     * @param int $maxTable Max number of rows to display in table display
     * @param string $separator Separator in table display
     * @param string $more There is more in table display
     */
    public function __construct(protected MaskRepository $maskRepository, $maxTable = 3, $separator = '<br />', $more = '...')
    {
        parent::__construct($maxTable, $separator, $more);
    }

    /**
     * Displays the content
     *
     * @param mixed $value
     * @return string
     */
    public function formatDetailed(mixed $value): ElementInterface|string
    {
        //\MUtil\EchoOut\EchoOut::track($value);
        if (is_array($value)) {
            $value = $this->maskRepository->applyMaskToRow($value);
        }

        return parent::formatDetailed($value);
    }

    /**
     * Displays the content
     *
     * @param string $value
     * @return string
     */
    public function formatTable(mixed $value): ElementInterface|string
    {
        if (is_array($value)) {
            $value = $this->maskRepository->applyMaskToRow($value);
        }

        return parent::formatTable($value);
    }
}
