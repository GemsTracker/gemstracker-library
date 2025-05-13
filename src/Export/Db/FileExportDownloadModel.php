<?php

declare(strict_types=1);

namespace Gems\Export\Db;

use Gems\Legacy\CurrentUserRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Ra\ArrayModelAbstract;

class FileExportDownloadModel extends ArrayModelAbstract
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        private readonly DbExportRepository $dbExportRepository,
        private readonly CurrentUserRepository $currentUserRepository,
        private readonly TranslatorInterface $translator,
    )
    {
        $metaModel = $metaModelLoader->createMetaModel('fileExportDownload');
        parent::__construct($metaModel);

        $this->metaModel->set('gfex_file_name', [
            'label' => $this->translator->_('Name'),
        ]);

        $this->metaModel->set('gfex_created', [
            'label' => $this->translator->_('Created'),
        ]);

        $this->metaModel->setKeys(['gfex_export_id']);
    }

    protected function _loadAll(): array
    {
        return $this->dbExportRepository->getAvailableDownloads($this->currentUserRepository->getCurrentUserId());
    }

    public function delete($filter = null): int
    {
        if (isset($filter['gfex_export_id'])) {
            $this->dbExportRepository->clearExportData(
                $filter['gfex_export_id'],
                $this->currentUserRepository->getCurrentUserId()
            );
            return 1;
        }
        return 0;
    }
}