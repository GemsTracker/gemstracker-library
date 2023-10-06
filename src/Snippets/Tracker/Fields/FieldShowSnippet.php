<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Fields
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Fields;

use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Tracker;
use Gems\Tracker\Field\FieldInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Fields
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class FieldShowSnippet extends ModelDetailTableSnippet
{

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected Tracker $tracker,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    public function getHtmlOutput()
    {
        $container = parent::getHtmlOutput();

        return $container;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList()
    {
//        $fid  = $this->request->getParam(\Gems\Model::FIELD_ID);
//        $prev = $this->db->fetchOne(
//            "SELECT gcj_id_job FROM gems__comm_jobs 
//                WHERE gcj_id_order < (SELECT gcj_id_order FROM gems__comm_jobs WHERE gcj_id_job = ?) 
//                ORDER BY gcj_id_order DESC",
//            $fid) ?: null;
//        $next = $this->db->fetchOne(
//            "SELECT gcj_id_job FROM gems__comm_jobs 
//                WHERE gcj_id_order > (SELECT gcj_id_order FROM gems__comm_jobs WHERE gcj_id_job = ?) 
//                ORDER BY gcj_id_order ASC",
//            $fid) ?: null;
//
//        $this->loader
        $queryParams = $this->requestInfo->getRequestQueryParams();
        $fid = null;
        if (isset($queryParams[\Gems\Model::FIELD_ID])) {
            $fid = $queryParams[\Gems\Model::FIELD_ID];
        }
        $sub = null;
        if (isset($queryParams['sub'])) {
            $sub = $queryParams['sub'];
        }
        $tid = null;
        if (isset($queryParams[\MUtil\Model::REQUEST_ID])) {
            $tid = $queryParams[\MUtil\Model::REQUEST_ID];
        }

        $trackEngine = $this->tracker->getTrackEngine($tid);
        $fieldDef    = $trackEngine->getFieldsDefinition();
        
        $prev = false;
        $next = null;
        foreach ($fieldDef->getFieldNames() as $key => $label) {
            if (isset($field)) {
                $previous = $field;
            }
            $field = $fieldDef->getField($key);
            if ($field instanceof FieldInterface) {
                if (($fid == $field->getFieldId()) && ($sub == $field->getFieldSub())) {
                    if (isset($previous)) {
                        $prev = [
                            'gtf_id_track' => $tid,
                            'gtf_id_field' => $previous->getFieldId(),
                            'sub' => $previous->getFieldSub(),
                        ];
                    } else {
                        $prev = null;
                    }
                } elseif (false !== $prev) {
                    $next = [
                        'gtf_id_track' => $tid,
                        'gtf_id_field' => $field->getFieldId(),
                        'sub' => $field->getFieldSub(),
                    ];
                    break;
                }
            }
        }
        
        // \MUtil\EchoOut\EchoOut::track($tid, $fid, $prev, $next);
        
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil\Html::raw($this->_('&lt; Previous')), $prev));
        $links->addCurrentParent($this->_('Cancel'));
        $links->addCurrentChildren();
        $links->addCurrentSiblings();

        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil\Html::raw($this->_('Next &gt;')), $next));

        return $links;
    }
}