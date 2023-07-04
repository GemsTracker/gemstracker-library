<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Validator\Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Validator;

use Laminas\Validator\AbstractValidator;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlTableModel;
use Zalt\Model\Validator\NameAwareValidatorInterface;

/**
 * @package    Gems
 * @subpackage Validator\Model
 * @since      Class available since version 1.0
 */
class ValidateSurveyExportCode extends AbstractValidator
    implements NameAwareValidatorInterface
{
    public const FOUND = 'found';

    public const START_NAME = 'survey__';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::FOUND => "A duplicate '%value%' export code was found.",
    ];

    protected string $name;

    public function __construct(
        protected int $surveyId,
        protected SqlTableModel $surveyModel,
        $options = null,
    )
    {
        parent::__construct($options);
    }

    public function isValid($value, array $context = [])
    {
        $this->setValue($value);

        foreach ($context as $field => $val) {
            if (str_starts_with($field, self::START_NAME)) {
                $sid = intval(substr($field, strlen(self::START_NAME)));
                if (($sid !== $this->surveyId) && ($value == $val)) {
                    $this->error(self::FOUND);
                    return false;
                }
            }
        }
        $filter['gsu_export_code'] = $value;
        $filter[MetaModelInterface::FILTER_NOT]['gsu_id_survey'] = $this->surveyId;

//        dump($filter);
        if ($this->surveyModel->loadCount($filter)) {
            $this->error(self::FOUND);
            return false;
        }

        return true;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}