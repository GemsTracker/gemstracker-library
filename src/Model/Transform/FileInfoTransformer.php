<?php

namespace Gems\Model\Transform;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Mime\MimeTypes;
use Zalt\Late\Late;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class FileInfoTransformer extends ModelTransformerAbstract
{


    public function __construct(
        protected readonly string $startPath = '',
        protected readonly array|null $allowedContentMimeTypes = null,
        protected int|null $maxContentSize = null,
    )
    {
    }

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
    {
        $newData = [];

        foreach($data as $key => $file) {
            if (!($file instanceof SplFileInfo)) {
                continue;
            }

            $relativePath = str_replace($this->startPath, '', $file->getRealPath());

            $newData[$key] = [
                'fullpath'  => $file->getRealPath(),
                'relpath'   => $relativePath,
                'urlpath'   => $this->fromNameToUrlSave($relativePath),
                'path'      => $file->getPath(),
                'filename'  => $file->getFilename(),
                'extension' => $file->getExtension(),
                'content'   => Late::call([$this, 'getContent'], $file),
                'size'      => $file->getSize(),
                'changed'   => (new \DateTimeImmutable())->setTimestamp($file->getMTime()),
            ];
        }

        return $newData;
    }

    /**
     * @param string $filename
     * @return string Remove \, / and . from name
     */
    public function fromNameToUrlSave(string $filename): string
    {
        return str_replace(['\\', '/', '.'], ['|', '|', '%2E'], $filename);
    }

    public function getContent(SplFileInfo $file): string|null
    {
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($file->getRealPath());

        if (is_int($this->maxContentSize) && $file->getSize() > $this->maxContentSize) {
            return null;
        }

        if (is_array($this->allowedContentMimeTypes) && !in_array($mimeType, $this->allowedContentMimeTypes)) {
            return null;
        }

        return $file->getContents();
    }
}