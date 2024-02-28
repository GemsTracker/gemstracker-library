<?php

namespace Gems\Model\Ra;

use Gems\Model\Transform\FileInfoTransformer;
use Symfony\Component\Finder\Finder;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Ra\ArrayModelAbstract;

class FolderModel extends ArrayModelAbstract
{
    protected Finder $finder;

    public function __construct(
        protected readonly string|Finder $dir,
        protected readonly MetaModelLoader $metaModelLoader,
        readonly bool $recursive = false,
        readonly bool $followSymlinks = false,
    )
    {
        $this->finder = $this->getFinder($dir, $recursive, $followSymlinks);

        $this->metaModel = new MetaModel($dir, $this->metaModelLoader);
        parent::__construct($this->metaModel);

        $this->metaModel->set('fullpath', [
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $this->metaModel->set('path', [
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $this->metaModel->set('filename', [
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $this->metaModel->set('relpath', [
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $this->metaModel->set('urlpath', [
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $this->metaModel->set('extension', [
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $this->metaModel->set('content', [
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $this->metaModel->set('size', [
            'type' => MetaModelInterface::TYPE_NUMERIC,
        ]);
        $this->metaModel->set('changed', [
            'type' => MetaModelInterface::TYPE_DATETIME,
        ]);

        $this->metaModel->setKeys(['urlpath']);
        $this->metaModel->addTransformer(new FileInfoTransformer( (is_string($dir) ? $dir : null) ));
    }

    protected function getFinder(string|Finder $dir, $recursive, $followSymlinks): Finder
    {
        if ($dir instanceof Finder) {
            return $dir;
        }

        $finder = new Finder();
        $finder->files()->in($dir);
        if ($followSymlinks) {
            $finder->followLinks();
        }
        if (!$recursive) {
            $finder->depth(0);
        }

        return $finder;
    }

    /**
     * Filtering in the Finder object, not in the results.
     *
     * @param array $filter
     * @return void
     */
    protected function finderFilter(array $filter): void
    {
        foreach ($filter as $name => $value) {
            if ($name == 'filename') {
                if (is_string($value)) {
                    $this->finder->name($value);
                } elseif (is_array($value) && !empty($value['like'])) {
                    $this->finder->name('*'.$value['like'].'*');
                }
            }
            if (is_array($value)) {
                $this->finderFilter($value);
            }
        }
    }

    /**
     * Sorting in the Finder object, not in the results.
     *
     * @param array $sort
     * @return void
     */
    protected function finderSort(array $sort): void
    {
        foreach ($sort as $name => $direction) {
            switch ($name) {
                case 'filename':
                    if ($direction == SORT_ASC) {
                        $this->finder->sortByName();
                    } else {
                        $this->finder->sortByName()->reverseSorting();
                    }
                    break;
                case 'size':
                    if ($direction == SORT_ASC) {
                        $this->finder->sortBySize();
                    } else {
                        $this->finder->sortBySize()->reverseSorting();
                    }
                    break;
                case 'changed':
                    if ($direction == SORT_ASC) {
                        $this->finder->sortByChangedTime();
                    } else {
                        $this->finder->sortByChangedTime()->reverseSorting();
                    }
                    break;
            }
            // We only support one column to sort on.
            break;
        }
    }

    public function load($filter = null, $sort = null, $columns = null): array
    {
        $filter = $this->checkFilter($filter);
        $sort   = $this->checkSort($sort);

        $this->finderFilter($filter);
        $this->finderSort($sort);

        $data = $this->_loadAll();

        return $this->metaModel->processAfterLoad($data);
    }

    protected function _loadAll(): array
    {
        return iterator_to_array($this->finder);
    }
}