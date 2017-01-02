<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Util;

/**
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 2, 2017 4:34:13 PM
 */
class CommTemplateUtil extends UtilAbstract
{
    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @param string $target Optional communications template target
     * @param string $code Optional communications template code
     * @return integer id of communications template or false if none exists
     */
    public function getCommTemplateForCode($code, $target = null)
    {
        $sql = "SELECT gct_id_template, gct_name FROM gems__comm_templates WHERE gct_code = ? ";

        $binds[] = $code;
        if ($target) {
            $binds[] = $target;
            $sql .= "AND gct_target = ? ";
        }
        $sql .= " ORDER BY gct_name LIMIT 1";
        
        return $this->db->fetchCol($sql, $binds);
    }

    /**
     *
     * @param string $target Optional communications template target
     * @param string $code Optional communications template code
     * @param boolean $addEmpty Add empty choice for drop down
     * @return array Of id => name
     */
    public function getCommTemplatesForTarget($target = null, $code = null, $addEmpty = false)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $target . '_' . $code;

        $sql = "SELECT gct_id_template, gct_name FROM gems__comm_templates ";

        $binds = [];
        if ($target) {
            $binds[] = $target;
            $sql .= "WHERE gct_target = ? ";
        }
        if ($code) {
            $binds[] = $code;
            if ($binds) {
                $sql .= "AND ";
            } else {
                $sql .= "WHERE ";
            }
            $sql .= "gct_code = ? ";
        }
        $sql .= " ORDER BY gct_name";

        $result = $this->_getSelectPairsCached($cacheId, $sql, $binds, ['commTemplates']);

        if ($addEmpty) {
            return $this->util->getTranslated()->getEmptyDropdownArray() + $result;
        }

        return $result;
    }
}
