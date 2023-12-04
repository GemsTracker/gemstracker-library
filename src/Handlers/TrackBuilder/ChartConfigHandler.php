<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <mmenno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Db\ResultFetcher;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Model\TrackBuilder\ChartConfigModel;
use Gems\Util\Translated;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Configuration for barchart snippets
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class ChartConfigHandler extends ModelSnippetLegacyHandlerAbstract
{
    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected Translated $translatedUtil,
        protected ResultFetcher $resultFetcher,
        protected readonly ChartConfigModel $chartConfigModel,
    ) {
        parent::__construct($responder, $translate, $cache);
    }

    protected function createModel(bool $detailed, string $action): ChartConfigModel
    {
        $trackId = null;
        if ($this->requestInfo->isPost()) {
            $data = $this->request->getParsedBody();
            if (array_key_exists('gcc_tid', $data) && !empty($data['gcc_tid'])) {
                $trackId = (int)$data['gcc_tid'];
            }
        }

        $this->chartConfigModel->applySettings($detailed, $trackId, $action);

        return $this->chartConfigModel;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('Chart config', 'Chart configs', $count);
    }
}
