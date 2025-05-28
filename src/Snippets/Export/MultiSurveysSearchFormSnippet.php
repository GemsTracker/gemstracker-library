<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

use Gems\Config\ConfigAccessor;
use Gems\Db\ResultFetcher;
use Gems\Export;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Tracker;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Export
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 04-Jul-2017 19:06:01
 */
class MultiSurveysSearchFormSnippet extends SurveyExportSearchFormSnippet
{
    protected function createSurveyElement(array $data): \Zend_Form_Element|array
    {
        $roundDescr = $data['gto_round_description'] ?? null;
        $trackId = $data['gto_id_track'] ?? null;

        $surveys = $this->getSurveysForExport($trackId, $roundDescr);

        $elements = $this->_createMultiCheckBoxElements(
            'gto_id_survey',
            $surveys['active'] ?? [],
            '<br/>',
        );
        foreach ($elements as $element) {
            if ($element instanceof \Zend_Form_Element_MultiCheckbox) {
                $element->setAttrib('class', 'auto-submit');
            }
        }
        array_unshift($elements, null);

        return $elements;
    }
}
