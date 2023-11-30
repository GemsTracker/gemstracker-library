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

    protected function _loadAll(): array
    {
        return iterator_to_array($this->finder);
    }
}