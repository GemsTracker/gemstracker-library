<?php

namespace Gems\Model\Transform;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Reference\W3CReference;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class HtmlSanitizeTransformer extends ModelTransformerAbstract
{
    protected HtmlSanitizer $sanitizer;

    /**
     * @param array $sanitizeFields array of either field names as values
     * or field names as keys and their specific html tag to sanitize for
     */
    public function __construct(protected array $sanitizeFields)
    {
        $this->sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig())->allowSafeElements()
        );
    }

    public function transformRowBeforeSave(MetaModelInterface $model, array $row)
    {
        foreach($this->sanitizeFields as $fieldName => $sanitizeFor) {
            if (is_int($fieldName)) {
                $fieldName = $sanitizeFor;
                $sanitizeFor = W3CReference::CONTEXT_BODY;
            }
            if (isset($row[$fieldName])) {
                $row[$fieldName] = $this->sanitizer->sanitizeFor($sanitizeFor, $row[$fieldName]);
            }
        }

        return $row;
    }
}