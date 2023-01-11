<?php

namespace Gems\Model\Transform;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class HtmlSanitizeTransformer extends ModelTransformerAbstract
{
    protected HtmlSanitizer $sanitizer;

    public function __construct(protected array $sanitizeFields)
    {
        $this->sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig())->allowSafeElements()
        );
    }

    public function transformRowBeforeSave(MetaModelInterface $model, array $row)
    {
        foreach($this->sanitizeFields as $fieldName) {
            if (isset($row[$fieldName])) {
                $row[$fieldName] = $this->sanitizer->sanitize($row[$fieldName]);
            }
        }

        return $row;
    }
}