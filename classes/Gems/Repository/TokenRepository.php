<?php

namespace Gems\Repository;

use Laminas\Db\Sql\Predicate\Expression;
use MUtil\Translate\Translator;

class TokenRepository
{
    public function __construct(protected Translator $translator)
    {}

    /**
     * Returns a status code => decription array
     *
     * @static $status array
     * @return array
     */
    public function getEveryStatus(): array
    {
        $status = [
            'U' => $this->translator->_('Valid from date unknown'),
            'W' => $this->translator->_('Valid from date in the future'),
            'O' => $this->translator->_('Open - can be answered now'),
            'P' => $this->translator->_('Open - partially answered'),
            'A' => $this->translator->_('Answered'),
            'I' => $this->translator->_('Incomplete - missed deadline'),
            'M' => $this->translator->_('Missed deadline'),
            'D' => $this->translator->_('Token does not exist'),
        ];

        return $status;
    }

    /**
     * An expression for calculating the show status for answers
     *
     * @param int $groupId
     * @return Expression
     */
    public function getShowAnswersExpression(int $groupId): Expression
    {
        return new Expression(sprintf(
            "CASE WHEN gsu_answers_by_group = 0 OR gsu_answer_groups LIKE '%%|%d|%%' THEN 1 ELSE 0 END",
            [$groupId]));
    }

    /**
     * Returns the class to display the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getStatusClass(string $value): string
    {
        switch ($value) {
            case 'A':
                return 'answered';
            case 'I':
                return 'incomplete';
            case 'M':
                return 'missed';
            case 'P':
                return 'partial';
            case 'O':
                return 'open';
            case 'U':
                return 'unknown';
            case 'W':
                return 'waiting';
            default:
                return 'empty';
        }
    }

    /**
     * An expression for calculating the token status
     *
     * @return Expression
     */
    public function getStatusExpression(): Expression
    {
        return new Expression("
            CASE
                WHEN gto_id_token IS NULL OR grc_success = 0 THEN 'D'
                WHEN gto_completion_time IS NOT NULL         THEN 'A'
                WHEN gto_valid_from IS NULL                  THEN 'U'
                WHEN gto_valid_from > CURRENT_TIMESTAMP      THEN 'W'
                WHEN gto_in_source = 1 AND gto_valid_until < CURRENT_TIMESTAMP THEN 'I'
                WHEN gto_valid_until < CURRENT_TIMESTAMP     THEN 'M'
                WHEN gto_in_source = 1                       THEN 'P'
                ELSE 'O'
            END
            ");
    }
}