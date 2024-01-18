<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\Agenda;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\Dependency\ActivationDependency;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class AppointmentShowSnippet extends \Gems\Snippets\ModelDetailTableSnippetAbstract
{
    protected ?DataReaderInterface $model = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected Agenda $agenda,
        protected MenuSnippetHelper $menuHelper,
        protected Model $modelLoader,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof \Gems\Model\AppointmentModel) {
            $this->model = $this->modelLoader->createAppointmentModel();
            $this->model->applyDetailSettings();

            $metaModel = $this->model->getMetaModel();
            $metaModel->addDependency(new ActivationDependency(
                $this->translate,
                $metaModel,
                $this->menuHelper,
            ));
        } else {
            $metaModel = $this->model->getMetaModel();
        }
        $metaModel->set('gap_id_episode', 'formatFunction', [$this, 'displayEpisode']);
        $metaModel->set('gap_admission_time', 'formatFunction', [$this, 'displayDate']);
        $metaModel->set('gap_discharge_time', 'formatFunction', [$this, 'displayDate']);

        return $this->model;
    }

    public function displayDate($date)
    {
        if (! $date instanceof \DateTimeInterface) {
            return $date;
        }
        $div = Html::create('div');
        $div->appendAttrib('class',  'calendar');
        $div->span(ucfirst($date->format('l j F Y')))->class = 'date';
        // $div->strong($date->toString());
        // $div->br();
        $td = $div->span($date->format('H:i'));
        $td->class = 'time middleAlign';
        $td->append(' ');
        $td->i(['class' => 'fa fa-stopwatch']);
        return $div;
    }

    /**
     * Display the episode
     * @param int $episodeId
     * @return string
     */
    public function displayEpisode($episodeId)
    {
        if (! $episodeId) {
            return null;
        }
        $episode = $this->agenda->getEpisodeOfCare($episodeId);

        if (! $episode->exists) {
            return $episodeId;
        }

        $keys = $this->requestInfo->getRequestMatchedParams();
        $keys[Model::EPISODE_ID] = $episodeId;
        $href = $this->menuHelper->getRouteUrl('respondent.episodes-of-care.show', $keys);

        if (! $href) {
            return $episode->getDisplayString();
        }

        return Html::create('a', $href, $episode->getDisplayString());
    }
}
