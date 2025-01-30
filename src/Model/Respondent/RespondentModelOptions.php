<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Respondent
 */

namespace Gems\Model\Respondent;

use Zalt\Model\MetaModelInterface;

/**
 * @package    Gems
 * @subpackage Model\Respondent
 * @since      Class available since version 2.0
 */
class RespondentModelOptions
{
    public function addNameToModel(MetaModelInterface $metaModel, $label)
    {
        $nameExpr['familyLast'] = "COALESCE(grs_last_name, '-')";
        $fieldList[] = 'grs_last_name';

        if ($metaModel->has('grs_partner_last_name')) {
            $nameExpr['partnerSep'] = "' - '";
            if ($metaModel->has('grs_partner_surname_prefix')) {
                $nameExpr['partnerPrefix'] = "COALESCE(CONCAT(' ', grs_partner_surname_prefix), '')";
                $fieldList[] = 'grs_partner_surname_prefix';
            }

            $nameExpr['partnerLast'] = "COALESCE(CONCAT(' ', grs_partner_last_name), '')";
            $fieldList[] = 'grs_partner_last_name';
        }
        $nameExpr['lastFirstSep'] = "', '";

        if ($metaModel->has('grs_first_name')) {
            if ($metaModel->has('grs_initials_name')) {
                $nameExpr['firstName']  = "COALESCE(grs_first_name, grs_initials_name, '')";
                $fieldList[] = 'grs_first_name';
                $fieldList[] = 'grs_initials_name';
            } else {
                $nameExpr['firstName']  = "COALESCE(grs_first_name, '')";
                $fieldList[] = 'grs_first_name';
            }
        } elseif ($metaModel->has('grs_initials_name')) {
            $nameExpr['firstName']  = "COALESCE(grs_initials_name, '')";
            $fieldList[] = 'grs_initials_name';
        }
        if ($metaModel->has('grs_surname_prefix')) {
            $nameExpr['familyPrefix']  = "COALESCE(CONCAT(' ', grs_surname_prefix), '')";
            $fieldList[] = 'grs_surname_prefix';
        }

        if ($metaModel->has('grs_partner_name_after') && $metaModel->has('grs_partner_last_name')) {
            $fieldList[] = 'grs_partner_name_after';

            $lastPrefix = isset($nameExpr['familyPrefix']) ? $nameExpr['familyPrefix'] . ', ' : '';
            $partnerPrefix = isset($nameExpr['partnerPrefix']) ? ', ' . $nameExpr['partnerPrefix'] : '';

            $columnExpr = "CASE 
                WHEN grs_partner_name_after = 0 AND grs_partner_name_after IS NOT NULL THEN
                    CONCAT(grs_partner_last_name, ' - ', $lastPrefix grs_last_name, " . $nameExpr['lastFirstSep'] . ', ' . $nameExpr['firstName'] .  "$partnerPrefix)
                ELSE 
                    CONCAT(" . implode(', ', $nameExpr) . ") 
                END";
        } else {
            $columnExpr = "CONCAT(" . implode(', ', $nameExpr) . ")";
        }


        $metaModel->set('name', [
            'label' => $label,
            'column_expression' => $columnExpr,
            'fieldlist' => $fieldList,
        ]);
    }
}
