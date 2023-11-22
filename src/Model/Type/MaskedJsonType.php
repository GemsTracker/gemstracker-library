<?php

namespace Gems\Model\Type;

use Gems\User\Mask\MaskRepository;
use Zalt\Html\ElementInterface;
use Zalt\Model\Type\JsonType;

class MaskedJsonType extends JsonType
{
    /**
     * @param int $maxTable Max number of rows to display in table display
     * @param string $separator Separator in table display
     * @param string $more There is more in table display
     */
    public function __construct(protected MaskRepository $maskRepository, int $maxTable = 3, string $separator = '<br />', string $more = '...')
    {
        parent::__construct($maxTable, $separator, $more);
    }

    /**
     * Displays the content
     */
    public function format(mixed $value): ElementInterface|string
    {
        if (is_array($value)) {
            $value = $this->maskRepository->applyMaskToRow($value);
        }

        return parent::format($value);
    }

    /**
     * Displays the content
     */
    public function formatTable(mixed $value): ElementInterface|string
    {
        if (is_array($value)) {
            $value = $this->maskRepository->applyMaskToRow($value);
        }

        return parent::formatTable($value);
    }
}
